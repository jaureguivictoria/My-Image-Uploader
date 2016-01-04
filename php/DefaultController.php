<?php

namespace Uakika\NFCBackendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Uakika\NFCBackendBundle\Forms\ChangePassForm;
use Uakika\NFCBackendBundle\Forms\ProfileForm;
use Uakika\NFCEntitiesBundle\Entity\CustomerConfiguration;
use Uakika\NFCEntitiesBundle\Entity\TempImage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DefaultController extends Controller {
    
    
    /**
     *  Shows the customer's profile
     *
     * @Route("/backend/profile", name="backend_edit_profile")
     * @Template()
     */
    public function showProfileAction(Request $request){       
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(new ProfileForm());
        $user = $this->container->get('security.context')->getToken()->getUser();
        $customer = $em->getRepository('UakikaNFCEntitiesBundle:Customer')->findOneBy(array('user' => $user));
        $event = $em->getRepository('UakikaNFCEntitiesBundle:Loyalty')->findOneBy(array('customer' => $customer, 'enabled' => 1));
        $timezones = $this->getTimezones($event);
        $session = $request->getSession();
        $actual_timezone = ($session->get('timezone')) ? $session->get('timezone') : 'UTC';
        $actual_language = ($session->get('language')) ? $session->get('language') : 'es';
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($actual_timezone));
        return $this->render('UakikaNFCBackendBundle:Default:profile.html.twig', array(
                    'form' => $form->createView(),
                    'actual_timezone' => $actual_timezone,
                    'actual_language' => $actual_language,
                    'timezones' => $timezones,
                    'now' => $now->format("d/m/Y H:i")
        ));
    }

    /*
     * Gets an array of the all the time zones and their time difference +/- GMT
     */
    public function getTimezones($loyalty){
        $em = $this->getDoctrine()->getManager();
        $now = $em->getRepository('UakikaNFCEntitiesBundle:CustomerConfiguration')->getDateTime($loyalty->getId());

        $zones_array = array();
        foreach (timezone_identifiers_list() as $key => $zone){
            $zones_array[$key]['zone'] = $zone;
            $dateTimezone = new \DateTimeZone($zone);
            $parsedOffset = (timezone_offset_get($dateTimezone, $now) / 60) / 60;
            $parsedOffset = str_replace(".5", ":30", $parsedOffset);
            $sign = (timezone_offset_get($dateTimezone, $now) >= 0) ? "+" : "";
            $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . $sign . $parsedOffset;
        }
        return $zones_array;
    }
    
    
    /**
     *  Saves the customer's profile
     * (Only AJAX)
     *
     * @Route("/backend/profile/save", name="backend_save_profile")
     */
    public function saveProfileAction(Request $request){
        $session = $this->container->get('session');
        $em = $this->getDoctrine()->getManager();
        $translator = $this->get('translator');
        $changepass = $request->get('changepass');
        $status = 1;
        $error = "";
        
        $user = $this->container->get('security.context')->getToken()->getUser();
        $customer = $em->getRepository('UakikaNFCEntitiesBundle:Customer')->findOneBy(array('user' => $user));
        $event = $em->getRepository('UakikaNFCEntitiesBundle:Loyalty')->findOneBy(array('customer' => $customer, 'enabled' => 1));
        $this_image = ($event->getImage() != null) ? $event->getImage()->getOriginalImage() : null;
        
        $language = $request->get('language');
        $timezone = $request->get('timezone');
        
        // See if image exists
        $temp_image_id = ($request->get("temp_image_id") != "") ? $request->get("temp_image_id") : NULL;
        if($temp_image_id != null){
            $loyalty_image = $em->getRepository('UakikaNFCEntitiesBundle:Loyalty')->uploadProfileImage($temp_image_id, $event);
            if ($loyalty_image){
                $request->getSession()->set('event_image', $loyalty_image->getOriginalImage());
                $this_image = $loyalty_image->getOriginalImage();
            } else {
                $request->getSession()->set('event_image', null);
                $this_image = null;
            }
        }

        // Password change
        $actual = $changepass['currentpassword'];
        $password = $changepass['password'];
        $first = $password['first'];
        $second = $password['second'];
        $userName = $user->getUsername();

        if (!empty($actual) && !empty($first) && !empty($second)){
            if ($second != $first){
                $status = "0";
                $error =  $translator->trans('profile.passwords_do_not_match');
            } else {
                $userManager = $this->get('fos_user.user_manager');
                $user = $userManager->loadUserByUsername($userName);
                $encoder = $this->get('security.encoder_factory')->getEncoder($user);
                $encodedPass = $encoder->encodePassword($first, $user->getSalt());
                $actualEncodedPass = $encoder->encodePassword($actual, $user->getSalt());

                if ($user->getPassword() != $actualEncodedPass){
                    $status = "0";
                    $error = $translator->trans('profile.incorrect_current_password')
                } else {
                    $user->setPassword($encodedPass);
                    // Graba en la base
                    $em->persist($user);
                    $em->flush();
                    $em->persist($customer);
                    $em->flush();
                }
            }
        }

        // Set timezone
        if (!empty($timezone)){
            $timezone_config = $em->getRepository('UakikaNFCEntitiesBundle:Configuration')->getTimezoneConfiguration();
            $em->getRepository('UakikaNFCEntitiesBundle:CustomerConfiguration')->saveConfiguration($customer, $timezone_config, $timezone);
            $request->getSession()->set('timezone', $timezone);
        }
        
        // Set language
        if (!empty($language)){
            $language_config = $em->getRepository('UakikaNFCEntitiesBundle:Configuration')->getLanguageConfiguration();
            $em->getRepository('UakikaNFCEntitiesBundle:CustomerConfiguration')->saveConfiguration($customer, $language_config, $language);
            $language = $em->getRepository('UakikaNFCEntitiesBundle:CustomerConfiguration')->findOneBy(
            array('customer' => $customer,
                  'configuration' => $language_config));
            $this_language = ($language) ? $language->getValue() : 'es';
            
            // Set chosen language
            $session->set('language', $this_language);
            $session->set('_locale', $this_language);
            $request->setLocale($this_language);        
            $translator = $this->get('translator');   
            $translator->setLocale($this_language);
        }

        // Change of name
        $name = $changepass['name'];
        $user->setFacebookName($name);
        $customer->setName($name);
        $em->persist($user);
        $em->flush();
        $em->persist($customer);
        $em->flush();

        return $this->render('UakikaNFCBackendBundle:Base:base.json.twig', array("data" => array(
                        'status' => $status,
                        'event_image' => $this_image,
                        'error' => $error)
        ));
    }
    
    /**
     *  Saves the uploaded temporal image
     *
     * @Route("/backend/image/upload", name="backend_temp_image_upload")
     * 
     */
    public function uploadImageAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $del_temp_img_id = $request->get('del_temp_img');
        $base64 = $request->get('image_base64');
        $base64_string = preg_replace( '/data:image\/.*;base64,/', '', $base64 ); 
        $image_name = sha1(uniqid(mt_rand(), true)) .".png";        
        $image = imagecreatefromstring(base64_decode($base64_string));
        
        $temp_image = new TempImage ();
        $temp_image->setOriginalFile ($image);
        $temp_image->setOriginalImage($image_name);
        $temp_image->setExtension("png");

        // Ejecuta el proceso de upload
        $path = $temp_image->getUploadRootDir()."/".$temp_image->getOriginalImage();
        imagepng($image, $path);
        
        $em->persist($temp_image);
        $em->flush();
        
        $this->deleteTempDropedImage($del_temp_img_id);
        
        return $this->render ( 'UakikaNFCBackendBundle:Base:base.json.twig', array (
                    "data" => array (
                        "status" => true,
                        "temp_id" => $temp_image->getId(),
                        "temp_path" => $temp_image->getOriginalImage()
                    )
            ) );
    }
    
    /* 
     * Deletes a temporal image dropped in the ImageUploader zone
     */
    public function deleteTempDropedImage($id){
		$em = $this->getDoctrine()->getManager();
		if(!empty($id)){
			$temp = $em->getRepository('UakikaNFCEntitiesBundle:TempImage')->find($id);
			if(!is_null($temp)){
                $temp->removeUpload();
                $em->remove($temp);
                $em->flush();
			}
		}
	}
    
}

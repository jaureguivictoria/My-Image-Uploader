<?php

namespace Uakika\NFCEntitiesBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * LoyaltyRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LoyaltyRepository extends EntityRepository {
    
    /* 
     * Saves the customer's profile image
     */
    public function uploadProfileImage($temp_image_id, $event) {

        $em = $this->getEntityManager();
        $image_id = NULL;
        $loyalty_image = null;

        if ($temp_image_id != null) {
            $actual_image = $event->getImage();
            if ($actual_image)
                $this->deleteEventImage($event);

            // Creates a new one, if it is 0 then the current image will be removed
            if ($temp_image_id != 0) {
                $loyalty_image = new LoyaltyImage();
                $temp_image = $em->getRepository('UakikaNFCEntitiesBundle:TempImage')->find($temp_image_id);
                rename($temp_image->getAbsolutePathOriginalImage(), $loyalty_image->getUploadRootDir() . "/" . $temp_image->getOriginalImage());

                $loyalty_image->setOriginalFile($temp_image->getOriginalFile());
                $loyalty_image->setOriginalImage($temp_image->getOriginalImage());
                $loyalty_image->setCroppedImage($temp_image->getOriginalImage());
                $loyalty_image->setExtension($temp_image->getExtension());

                // Saves
                $em->persist($loyalty_image);
                $em->flush();

                $temp_image->removeUpload();
                $em->remove($temp_image);
                $em->flush();

                $event->setImage($loyalty_image);
            }
        }
        return $loyalty_image;
    }
   
}

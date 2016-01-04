<?php
namespace Uakika\NFCEntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Image\Image;

/**
 * Uakika\NFCEntitiesBundle\Entity\BaseImage
 *
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */

class BaseImage {
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $originalImage
     *
     * @ORM\Column(name="original_image", type="string", length=255, nullable=true)
     */
    private $originalImage;

    /**
     * @var string $croppedImage
     *
     * @ORM\Column(name="cropped_image", type="string", length=255, nullable=true)
     */
    private $croppedImage;

    /**
     * @var string $extension
     *
     * @ORM\Column(name="extension", type="string", length=255, nullable=false)
     */
    private $extension;

    /**
     * @var int $targ_h
     *
     * @ORM\Column(name="targ_h", type="integer", nullable=true)
     */
    private $targ_h = 160;
    
    /**
     * @var int $targ_w
     *
     * @ORM\Column(name="targ_w", type="integer", nullable=true)
     */
    private $targ_w = 160;
    
    /**
     * @var int $width
     *
     * @ORM\Column(name="width", type="integer", nullable=true)
     */
    private $width;

    /**
     * @var int $height
     *
     * @ORM\Column(name="height", type="integer", nullable=true)
     */
    private $height;

    /**
     * @var int $y
     *
     * @ORM\Column(name="y", type="integer", nullable=true)
     */
    private $y;

    /**
     * @var int $x
     *
     * @ORM\Column(name="x", type="integer", nullable=true)
     */
    private $x;

    /**
     * @Assert\Image(
     *      maxSize="4M",
     *      minWidth = 100,
     *      minHeight = 100,
     *      mimeTypes = {"image/png", "image/jpeg"},
     *      mimeTypesMessage = "Please upload a valid png/jpeg of at least 100x100 and 4M max size"
     * )
     */
    public $originalFile;

    /**
     * @Assert\Image(maxSize="4M")
     */
    public $croppedFile;


    public function getAbsolutePathOriginalImage() {
        return null === $this->originalImage ? null : $this->getUploadRootDir() . '/' . $this->originalImage;
    }

    public function getWebPathOriginalImage() {
        return null === $this->originalImage ? null : $this->getUploadDir() . '/' . $this->originalImage;
    }

    public function getAbsolutePathCroppedImage() {
        return null === $this->croppedImage ? null : $this->getUploadRootDir() . '/' . $this->croppedImage;
    }

    public function getWebPathCroppedImage() {
        return null === $this->croppedImage ? null : $this->getUploadDir() . '/' . $this->croppedImage;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set originalImage
     *
     * @param string $originalImage
     * @return CustomReward
     */
    public function setOriginalImage($originalImage) {
        $this->originalImage = $originalImage;

        return $this;
    }

    /**
     * Get originalImage
     *
     * @return string
     */
    public function getOriginalImage() {
        return $this->originalImage;
    }

    /**
     * Set croppedImage
     *
     * @param string $croppedImage
     * @return CustomReward
     */
    public function setCroppedImage($croppedImage) {
        $this->croppedImage = $croppedImage;

        return $this;
    }

    /**
     * Get croppedImage
     *
     * @return string
     */
    public function getCroppedImage() {
        return $this->croppedImage;
    }

    /**
     * Metodos para el manejo del upload de la imagen
     */

    public function setOriginalFile($originalFile) {
        $this->originalFile = $originalFile;
    }
    
    
    /**
     * Get originalFile
     * 
     * @return @Assert\Image
     */
    public function getOriginalFile(){
        
        return $this->originalFile;
    }

    public function setExtension($extension){
        $this->extension = $extension;
    }

    public function getExtension(){
        return $this->extension;
    }

    public function preUpload() {
        if (null !== $this->originalFile) {
            $this->setExtension($this->originalFile->guessExtension());
            // Genera un nombre para la imagen
            $this->setOriginalImage(sha1(uniqid(mt_rand(), true)) . '.' . $this->getExtension());
            $this->setCroppedImage($this->getOriginalImage());
        }
    }

    public function upload() {
        // the file property can be empty if the field is not required
        if (null === $this->originalFile) {
            return;
        }

        // move takes the target directory and then the target filename to move to
        $this->originalFile->move($this->getUploadRootDir(), $this->getOriginalImage());

        // clean up the file property as you won't need it anymore
        unset($this->originalFile);
    }

    public function crop() {
        $thumb_width = $this->getTargW();
        $thumb_height = $this->getTargH();

        // Toma los parametros de la imagen
        $src = $this->getAbsolutePathOriginalImage();
        if (null === $src) {
            exit;
        }
        $width = $this->getWidth();
        $height = $this->getHeight();
        
        $original_aspect = $width / $height;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ( $original_aspect >= $thumb_aspect ){
           // If image is wider than thumbnail (in aspect ratio sense)
           $new_height = $thumb_height;
           $new_width = $width / ($height / $thumb_height);
        } else {
           // If the thumbnail is wider than the image
           $new_width = $thumb_width;
           $new_height = $height / ($width / $thumb_width);
        }

        $thumb = imagecreatetruecolor( $thumb_width, $thumb_height );       

        if($this->getExtension() == "jpeg"){
            $jpeg_quality = 90;
            $image = imagecreatefromjpeg($src);
            imagecopyresampled($thumb,
               $image,
               0 - ($new_width - $thumb_width) / 2, // Center the image horizontally
               0 - ($new_height - $thumb_height) / 2, // Center the image vertically
               0, 0,
               $new_width, $new_height,
               $width, $height);
            // Genera nuevo archivo con la imagen cropeada
            $croppedFilePath = str_replace(".jpeg", "-crop.jpeg", $src);
            imagejpeg($thumb, $croppedFilePath, $jpeg_quality);
        } else {
            $image = imagecreatefrompng($src);
            imagecopyresampled($thumb,
               $image,
               0 - ($new_width - $thumb_width) / 2, // Center the image horizontally
               0 - ($new_height - $thumb_height) / 2, // Center the image vertically
               0, 0,
               $new_width, $new_height,
               $width, $height);
            // Genera nuevo archivo con la imagen cropeada
            $croppedFilePath = str_replace(".png", "-crop.png", $src);
            imagepng($thumb, $croppedFilePath);
        }

        $this->setCroppedImage(substr($croppedFilePath, strrpos($croppedFilePath, '/') + 1)); // Toma el nombre del archivo del path entero
        return true;
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload() {
        if ($file = $this->getAbsolutePathOriginalImage()) {
            if (file_exists($file))
                unlink($file);
        }
        if ($croppedFile = $this->getAbsolutePathCroppedImage()) {
            if (file_exists($croppedFile))
                unlink($croppedFile);
        }
    }

    /**
     * Set targ_h
     *
     * @param integer $targ_h
     * @return CustomRewardImage
     */
    public function setTargH($targ_h)
    {
        $this->targ_h = $targ_h;

        return $this;
    }

    /**
     * Get targ_h
     *
     * @return integer
     */
    public function getTargH()
    {
        return $this->targ_h;
    }
    
    /**
     * Set targ_w
     *
     * @param integer $targ_w
     * @return CustomRewardImage
     */
    public function setTargW($targ_w)
    {
        $this->targ_w = $targ_w;

        return $this;
    }

    /**
     * Get targ_w
     *
     * @return integer
     */
    public function getTargW()
    {
        return $this->targ_w;
    }
    
    /**
     * Set width
     *
     * @param integer $width
     * @return CustomRewardImage
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     * @return CustomRewardImage
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set y
     *
     * @param integer $y
     * @return CustomRewardImage
     */
    public function setY($y)
    {
        $this->y = $y;

        return $this;
    }

    /**
     * Get y
     *
     * @return integer
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Set x
     *
     * @param integer $x
     * @return CustomRewardImage
     */
    public function setX($x)
    {
        $this->x = $x;

        return $this;
    }

    /**
     * Get x
     *
     * @return integer
     */
    public function getX()
    {
        return $this->x;
    }

}

<?php
namespace Uakika\NFCEntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Uakika\NFCEntitiesBundle\Entity\TempImage
 *
 * @ORM\Table(name="temp_image")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class TempImage extends BaseImage{


    protected function getUploadDir() {
        // get rid of the __DIR__ so it doesn't screw when displaying uploaded doc/image in the view.
        return 'upload/temp';
    }

}

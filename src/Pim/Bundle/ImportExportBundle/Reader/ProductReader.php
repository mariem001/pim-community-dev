<?php

namespace Pim\Bundle\ImportExportBundle\Reader;

use Symfony\Component\Validator\Constraints as Assert;
use Pim\Bundle\ImportExportBundle\Validator\Constraints\Channel as ChannelConstraint;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\ProductManager;
use Pim\Bundle\CatalogBundle\Entity\Channel;

/**
 * Product reader
  *
  * @author    Gildas Quemener <gildas@akeneo.com>
  * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
  * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
  */
class ProductReader extends ORMReader
{
    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ChannelConstraint
     */
    protected $channel;

    /** @var ProductManager */
    protected $productManager;

    /** @var ChannelManager */
    protected $channelManager;

    /**
     * @param ProductManager $productManager
     * @param ChannelManager $channelManager
     */
    public function __construct(
        ProductManager $productManager,
        ChannelManager $channelManager
    ) {
        $this->productManager = $productManager;
        $this->channelManager = $channelManager;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if (!$this->query) {
            $channel = current($this->channelManager->getChannels(array('code' => $this->channel)));
            if (!$channel) {
                throw new \InvalidArgumentException(
                    sprintf('Could not find the channel %s', $this->channel)
                );
            }

            $this->getCompletenessRepository()->createChannelCompletenesses($channel);

            $this->query = $this->getProductRepository()
                ->buildByChannelAndCompleteness($channel)
                ->getQuery();
        }

        return parent::read();
    }

    /**
     * Set channel
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * Get channel
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array(
            'channel' => array(
                'type' => 'choice',
                'options' => array(
                    'choices' => $this->channelManager->getChannelChoices(),
                    'required' => true
                )
            )
        );
    }

    /**
     * Get the product repository
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getProductRepository()
    {
        return $this->productManager->getFlexibleRepository();
    }

    /**
     * Get the completeness repository
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getCompletenessRepository()
    {
        return $this
            ->productManager
            ->getStorageManager()
            ->getRepository('PimCatalogBundle:Completeness');
    }
}

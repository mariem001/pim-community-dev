<?php

namespace Context\Page\VariantGroup;

use Context\Page\ProductGroup\Creation as GroupCreation;

/**
 * Variant group creation page
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Creation extends GroupCreation
{
    /**
     * @var array
     */
    protected $elements = array(
        'Axis' => array('css' => '#pim_catalog_group_form_attributes')
    );

    /**
     * Select the axis
     * @param string $axis
     */
    public function selectAxis($axis)
    {
        $this->getElement('Axis')->selectOption($axis);
    }
}

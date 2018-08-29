<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Bus\Commands\ComponentGroup;

/**
 * This is the add component group command.
 *
 * @author James Brooks <james@alt-three.com>
 */
final class AddComponentGroupCommand
{
    /**
     * The component group name.
     *
     * @var string
     */
    public $name;

    /**
     * The component group description.
     *
     * @var int
     */
    public $order;

    /**
     * Is the component group collapsed?
     *
     * @var int
     */
    public $collapsed;

    /**
     * The validation rules.
     *
     * @var string[]
     */

    /**
     * The group this belongs to.
     *
     * @var int
     */
    public $parent_id;

    public $rules = [
        'name'      => 'required|string',
        'order'     => 'int',
        'collapsed' => 'int|between:0,3',
        'parent_id'  => 'int',
    ];

    /**
     * Create a add component group command instance.
     *
     * @param string $name
     * @param int    $order
     * @param int    $collapsed
     * @param int    $parent_id
     *
     * @return void
     */
    public function __construct($name, $order, $collapsed, $parent_id)
    {
        $this->name = $name;
        $this->order = (int) $order;
        $this->collapsed = $collapsed;
        $this->parent_id = $parent_id;
    }
}

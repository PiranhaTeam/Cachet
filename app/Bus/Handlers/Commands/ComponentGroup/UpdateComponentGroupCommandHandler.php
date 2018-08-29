<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Bus\Handlers\Commands\ComponentGroup;

use CachetHQ\Cachet\Bus\Commands\ComponentGroup\UpdateComponentGroupCommand;
use CachetHQ\Cachet\Bus\Events\ComponentGroup\ComponentGroupWasUpdatedEvent;

class UpdateComponentGroupCommandHandler
{
    /**
     * Handle the update component group command.
     *
     * @param \CachetHQ\Cachet\Bus\Commands\ComponentGroup\UpdateComponentGroupCommand $command
     *
     * @return \CachetHQ\Cachet\Models\ComponentGroup
     */
    public function handle(UpdateComponentGroupCommand $command)
    {
        $group = $command->group;
        $group->update($this->filter($command));

        event(new ComponentGroupWasUpdatedEvent($group));

        return $group;
    }

    /**
     * Filter the command data.
     *
     * @param \CachetHQ\Cachet\Bus\Commands\ComponentGroup\UpdateComponentGroupCommand $command
     *
     * @return array
     */
    protected function filter(UpdateComponentGroupCommand $command)
    {
        $group = $command->group;
        $disallowed_parent_ids = $group->all_subgroups_ordered()->get()->pluck('id')->toArray();
        array_push($disallowed_parent_ids, $group->id);

        $params = [
            'name'      => $command->name,
            'order'     => $command->order,
            'collapsed' => $command->collapsed,
            'parent_id' => $command->parent_id,
        ];

        return array_filter($params, function ($val, $key) use ($disallowed_parent_ids) {
            if($key === 'parent_id'){
                return !in_array($val, $disallowed_parent_ids);
            }
            return $val !== null;
        }, ARRAY_FILTER_USE_BOTH);
    }
}

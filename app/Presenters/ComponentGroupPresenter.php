<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Presenters;

use CachetHQ\Cachet\Presenters\Traits\TimestampsTrait;
use Illuminate\Contracts\Support\Arrayable;
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\Facades\AutoPresenter;

class ComponentGroupPresenter extends BasePresenter implements Arrayable
{
    use TimestampsTrait;

    /**
     * Returns the status for this group, determined by the minimum of:
     * - Major outage, if all immediate components are down
     * - Partial outage, if some immediate components are down
     * - Lowest status of any nested subgroup
     *
     * @return int
     */
    public function status() {
        return max($this->immediate_group_status(), $this->lowest_subgroup_status());
    }

    /**
     * Returns the status color as determined by the status() function
     *
     * @return string|null
     */
    public function status_color() {
        switch ($this->status()) {
            case 1: return 'greens';
            case 2: return 'blues';
            case 3: return 'yellows';
            case 4: return 'reds';
        }
    }


    /**
     * Returns the lowest nested component status for the group, readable by humans.
     *
     * @return string|null
     */
    public function human_status() {
        return trans('cachet.components.status.'.$this->status());
    }

    /**
     * Checks all of the immediate components in a group and returns:
     * - Operational (1) if all components online (Status of 1 or 2)
     * - Partial Outage (3) if some components experiencing outage (3 or 4)
     * - Major Outage (4) if all components experiencing outage (3 or 4)
     *
     * @return int
     */
    public function immediate_group_status(){
        $status = 1;
        $immediate_components = $this->wrappedObject->enabled_components_lowest()->get();
        if($immediate_components->count() > 0) {
            $count_online = 0;
            $count_outage = 0;
            foreach($immediate_components as $component){
                if(AutoPresenter::decorate($component)->status < 3 ){
                    $count_online++;
                } else{
                    $count_outage++;
                }
            }
            if($count_online === 0 ){
                # Group has a major outage if no online components
                $status = 4;
            } else if($count_outage === 0){
                # Group is operational if not components have outages
                $status = 1;
            } else {
                # Group has a partial outage is some components have outages
                $status = 3;
            }
        }
        return $status;
    }

    /**
     * Returns the lowest subgroup status.
     *
     * @return int
     */
    public function lowest_subgroup_status() {
        $min_status = 1;
        $subgroups = $this->wrappedObject->all_subgroups_ordered()->get();
        if($subgroups->count() > 0) {
            foreach($subgroups as $subgroup) {
                $subgroup_status = AutoPresenter::decorate($subgroup)->status;
                if($subgroup_status > $min_status) {
                    $min_status = $subgroup_status;
                }
            }
        }
        return $min_status;
    }


    /**
     * Returns the lowest nested component status, readable by humans.
     *
     * @return string|null
     */
    public function lowest_enabled_component_human_status()
    {
        if ($component = $this->wrappedObject->all_enabled_components_lowest()->first()) {
            return AutoPresenter::decorate($component)->human_status;
        }
    }

    /**
     * Returns the lowest nested component status.
     *
     * @return string|null
     */
    public function lowest_enabled_component_status()
    {
        if ($component = $this->wrappedObject->all_enabled_components_lowest()->first()) {
            return AutoPresenter::decorate($component)->status;
        }
    }

    /**
     * Returns the lowest nested component status color.
     *
     * @return string|null
     */
    public function lowest_enabled_component_status_color()
    {
        if ($component = $this->wrappedObject->all_enabled_components_lowest()->first()) {
            return AutoPresenter::decorate($component)->status_color;
        }
    }

    /**
     * Determine the class for collapsed/uncollapsed groups.
     *
     * @return string
     */
    public function collapse_class()
    {
        return $this->is_collapsed() ? 'ion-ios-plus-outline' : 'ion-ios-minus-outline';
    }

    /**
     * Determine if the group should be collapsed.
     *
     * @return bool
     */
    public function is_collapsed()
    {
        if ($this->wrappedObject->collapsed === 0) {
            return false;
        } elseif ($this->wrappedObject->collapsed === 1) {
            return true;
        }

        return $this->wrappedObject->components->filter(function ($component) {
            return $component->status > 1;
        })->count() === 0;
    }

    /**
     * Convert the presenter instance to an array.
     *
     * @return string[]
     */
    public function toArray()
    {
        return array_merge($this->wrappedObject->toArray(), [
            'created_at'          => $this->created_at(),
            'updated_at'          => $this->updated_at(),
            'lowest_human_status' => $this->human_status(),
            'status'              => $this->status()
        ]);
    }
}

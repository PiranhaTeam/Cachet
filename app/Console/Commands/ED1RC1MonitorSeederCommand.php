<?php

namespace CachetHQ\Cachet\Console\Commands;

use CachetHQ\Cachet\Models\Component;
use CachetHQ\Cachet\Models\ComponentGroup;
use CachetHQ\Cachet\Models\Incident;
use CachetHQ\Cachet\Models\IncidentTemplate;
use CachetHQ\Cachet\Models\Metric;
use CachetHQ\Cachet\Models\MetricPoint;
use CachetHQ\Cachet\Models\Subscriber;
use CachetHQ\Cachet\Models\User;
use CachetHQ\Cachet\Settings\Repository;
use DateInterval;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * This is the demo seeder command.
 *
 * @author Andrew Luo <andrew.luo@logmein.com>
 */
class ED1RC1MonitorSeederCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'lmi:seed {seed_path=logmein/ed1_rc1_monitor_seed.json} {monitor_path=logmein/cachet-monitor.json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds Cachet with monitoring data.';

    /**
     * The settings repository.
     *
     * @var \CachetHQ\Cache\Settings\Repository
     */
    protected $settings;

    /**
     * The config file in json format
     *
     * @var JSON
     */
    protected $seed_config;

    /**
     * The monitoring config file in json format
     *
     * @var JSON
     */
    protected $monitor_config;


    /**
     * Create a new seeder command instance.
     *
     * @param \CachetHQ\Cache\Settings\Repository $settings
     *
     * @return void
     */
    public function __construct(Repository $settings)
    {
        parent::__construct();

        $this->settings = $settings;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (!$this->confirmToProceed()) {
            return;
        }
        $this->seed_config = json_decode(file_get_contents($this->argument('seed_path')));
        $this->monitor_config = json_decode(file_get_contents($this->argument('monitor_path')));

        $this->seedAllComponents();
        $this->seedIncidents();
        $this->seedIncidentTemplates();
        $this->seedMetricPoints();
        $this->seedMetrics();
        $this->seedSettings();
        $this->seedSubscribers();
        $this->seedUsers();
        $this->info('Database seeded with monitoring data successfully!');
    }

    /**
     * Seed the component groups table.
     *
     * @return void
     */
    protected function seedAllComponents()
    {
        ComponentGroup::truncate();
        Component::truncate();
        //$this->monitor_config->monitors = [];

        $order = 1;
        foreach($this->seed_config->groups as $group){
            $this->seedComponentGroup($group, $order, 0);
            $order++;
        }
        file_put_contents($this->argument('monitor_path'), json_encode($this->monitor_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function seedComponentGroup($group, int $order, int $parent_id){
        $group_data = [
            'name'      => $group->name,
            'order'     => $order,
            'collapsed' => 3,
            'parent_id' => $parent_id,
        ];
        $group_created = ComponentGroup::create($group_data);;
        $subgroup_order = 1;
        foreach($group->groups as $subgroup){
            $this->seedComponentGroup($subgroup, $subgroup_order, $group_created->getId());
            $subgroup_order++;
        }
        $component_order = 1;
        foreach($group->components as $component) {
            $this->seedComponent($component, $component_order, $group_created->getId());
            $component_order++;
        }
    }

    /**
     * Seed the components table.
     *
     * @return void
     */
    protected function seedComponent($component, int $order, int $group_id)
    {
        $component_data = [
                'name'        => $component->name,
                'description' => $component->description,
                'status'      => 1,
                'order'       => $order,
                'group_id'    => $group_id,
                'link'        => $component->link,
        ];
        $component_created = Component::create($component_data);
        $monitor = [
            'name'          =>  $component->name,
            'target'        =>  $component->monitoring_url,
            'strict'        =>  false,
            'method'        =>  "GET",
            'component_id'  =>  $component_created->getId(),
            'template'      =>  [
                'investigating' => [
                    'subject'   =>  "{{ .Monitor.Name }} - {{ .SystemName }}",
                    'message'   =>  "{{ .Monitor.Name }} check **failed** (server time: {{ .now }})\n\n{{ .FailReason }}"
                ],
                'fixed'         => [
                    'subject'   =>  "I HAVE BEEN FIXED"
                ]
            ],
            'interval'      =>  5,
            'timeout'       =>  5,
            'threshold'     =>  80,
            'headers'       =>  $component->headers,
            'expected_status_code'=>200,
            'expected_body' =>  $component->expected_body
        ];
        array_push($this->monitor_config->monitors, $monitor);
    }

    /**
     * Seed the incidents table.
     *
     * @return void
     */
    protected function seedIncidents()
    {
        Incident::truncate();
    }

    /**
     * Seed the incident templates table.
     *
     * @return void
     */
    protected function seedIncidentTemplates()
    {
        IncidentTemplate::truncate();
    }

    /**
     * Seed the metric points table.
     *
     * @return void
     */
    protected function seedMetricPoints()
    {
        MetricPoint::truncate();
    }

    /**
     * Seed the metrics table.
     *
     * @return void
     */
    protected function seedMetrics()
    {
        Metric::truncate();
    }

    /**
     * Seed the settings table.
     *
     * @return void
     */
    protected function seedSettings()
    {
        $defaultSettings = [
            [
                'key'   => 'app_name',
                'value' => 'LMI ED1/RC1 Status Page',
            ], [
                'key'   => 'app_domain',
                'value' => 'http://localhost',
            ], [
                'key'   => 'show_support',
                'value' => '1',
            ], [
                'key'   => 'app_locale',
                'value' => 'en',
            ], [
                'key'   => 'app_timezone',
                'value' => 'America/Los_Angeles',
            ], [
                'key'   => 'app_incident_days',
                'value' => '7',
            ], [
                'key'   => 'app_analytics',
                'value' => 'UA-58442674-3',
            ], [
                'key'   => 'app_analytics_gs',
                'value' => 'GSN-712462-P',
            ], [
                'key'   => 'display_graphs',
                'value' => '1',
            ], [
                'key'   => 'app_about',
                'value' => 'Status page for LogMeIn ED and RC environments, for internal use only.',
            ], [
                'key'   => 'enable_subscribers',
                'value' => '0',
            ],
        ];

        $this->settings->clear();

        foreach ($defaultSettings as $setting) {
            $this->settings->set($setting['key'], $setting['value']);
        }
    }

    /**
     * Seed the subscribers.
     *
     * @return void
     */
    protected function seedSubscribers()
    {
        Subscriber::truncate();
    }

    /**
     * Seed the users table.
     *
     * @return void
     */
    protected function seedUsers()
    {
        $users = [
            [
                'username' => 'logmein',
                'password' => 'password',
                'email'    => 'andrewluo@logmein.com',
                'level'    => User::LEVEL_ADMIN,
                'api_key'  => '9yMHsdioQosnyVK4iCVR',
            ],
        ];

        User::truncate();

        foreach ($users as $user) {
            User::create($user);
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}

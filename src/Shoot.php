<?php
/**
 * Shoot plugin for Craft CMS 3.x
 *
 * Screenshot stuff.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2019 Værsågod
 */

namespace vaersaagod\shoot;

use vaersaagod\shoot\models\Settings;
use vaersaagod\shoot\services\ShootService;
use vaersaagod\shoot\variables\ShootVariable;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Class Shoot
 *
 * @author    Værsågod
 * @package   Shoot
 * @since     2.0.0
 *
 * @property  ShootService $shoot
 */
class Shoot extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Shoot
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '2.0.0';

    // Public Methods
    // =========================================================================

//    public function __construct($id, $parent = null, array $config = [])
//    {
//        Craft::dd($config);
//        parent::__construct($id, $parent, $config);
//    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register services
        $this->setComponents([
            'shoot' => ShootService::class,
        ]);

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'vaersaagod\shoot\console\controllers';
        }

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'shoot/default';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'shoot/default/do-something';
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('shoot', ShootVariable::class);
            }
        );

        Craft::info(
            Craft::t(
                'shoot',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );

    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(array $config = [])
    {
        return new Settings();
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: mmikkel
 * Date: 2019-04-22
 * Time: 10:49
 */

namespace vaersaagod\shoot\models;

use vaersaagod\shoot\traits\DeprecatedSettings;

use Craft;
use craft\base\Model;

/**
 * Class Settings
 * @package vaersaagod\shoot\models
 *
 * @mixin DeprecatedSettings
 */
class Settings extends Model
{

    /**
     * @var string|null
     */
    public $systemPath;

    /**
     * @var string|null
     */
    public $chromiumPath;

    /**
     * @var string|null
     */
    public $baseUrl;

    /**
     * @var string|null
     */
    public $publicRoot;

    /**
     * @var string|null
     */
    public $defaultExtension;

    private $deprecatedSettings = [
        'shootSystemPath' => 'systemPath',
        'shootUrl' => 'baseUrl',
        'shootPublicRoot' => 'publicRoot',
    ];

    /**
     * @param string $name
     * @return mixed|string|null
     * @throws \yii\base\UnknownPropertyException
     */
    public function __get($name)
    {
        if ($newSetting = $this->deprecatedSettings[$name]) {
            Craft::$app->getDeprecator()->log("shootSettings.{$name}", "The `{$name}` setting is deprecated. Use `{$newSetting}` instead.");
            return $this->$newSetting;
        }
        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function setAttributes($settings, $safeOnly = true)
    {

        // Handle deprecated settings from config
        foreach ($this->deprecatedSettings as $deprecatedSetting => $newSetting) {
            if (!!($settings[$deprecatedSetting] ?? null) && !($settings[$newSetting] ?? null)) {
                Craft::$app->getDeprecator()->log("shootSettings.{$deprecatedSetting}", "The `{$deprecatedSetting}` setting is deprecated. Use `{$newSetting}` instead.");
                $settings[$newSetting] = $settings[$deprecatedSetting];
                unset($settings[$deprecatedSetting]);
            }
        }

        // Parse aliases and/or environment variables
        foreach ($settings as $key => $value) {
            if (!$value) {
                continue;
            }
            $settings[$key] = Craft::parseEnv($value);
        }

        parent::setAttributes($settings, $safeOnly);
    }
}

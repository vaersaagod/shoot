<?php
/**
 * Shoot plugin for Craft CMS 3.x
 *
 * Screenshot stuff.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2019 Værsågod
 */

namespace vaersaagod\shoot\variables;

use vaersaagod\shoot\Shoot;
use vaersaagod\shoot\models\ShootModel;

use Craft;

/**
 * @author    Værsågod
 * @package   Shoot
 * @since     2.0.0
 */
class ShootVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param string $input
     * @param array $opts
     * @return ShootModel
     * @throws \Throwable
     */
    public function html(string $input, array $opts = []): ShootModel
    {
        return Shoot::$plugin->shoot->html($input, $opts);
    }

    /**
     * @param string $url
     * @param array $opts
     * @return ShootModel
     * @throws \Throwable
     */
    public function url(string $url, array $opts = []): ShootModel
    {
        return Shoot::$plugin->shoot->url($url, $opts);
    }

    /**
     * @param string|array $paths
     * @param array $opts
     * @param array $vars
     * @param bool $devMode
     * @return mixed|string
     * @throws \Throwable
     */
    public function template($paths, array $opts = [], array $vars = [], bool $devMode = false)
    {
        return Shoot::$plugin->shoot->template($paths, $vars, $opts, $devMode);
    }
}

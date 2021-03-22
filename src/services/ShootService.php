<?php
/**
 * Shoot plugin for Craft CMS 3.x
 *
 * Screenshot stuff.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2019 Værsågod
 */

namespace vaersaagod\shoot\services;

use craft\web\View;
use vaersaagod\shoot\Shoot;
use vaersaagod\shoot\models\Settings;
use vaersaagod\shoot\models\ShootModel;

use Spatie\Browsershot\Browsershot;
use Spatie\Image\Manipulations;

use Psr\Log\LogLevel;

use Craft;
use craft\base\Component;

/**
 * @author    Værsågod
 * @package   Shoot
 * @since     2.0.0
 */
class ShootService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Screenshot HTML
     *
     * @param string $input
     * @param array $opts
     * @return ShootModel
     * @throws \Throwable
     */
    public function html(string $input, array $opts = [])
    {

        /** @var Settings $settings */
        $settings = Shoot::$plugin->getSettings();

        $opts = \array_merge([], $opts);
        $extension = ($opts['extension'] ?? $settings->defaultExtension ?: 'png');
        $filename = $opts['filename'] ?? \md5($input);

        $filename = $this->buildFilename($filename, $extension, $opts);
        $filepath = $this->getPath($filename);

        if (\file_exists($filepath)) {
            return new ShootModel($filepath, $this->getUrl($filename));
        }

        $html = $this->prepareHtml($input);

        try {
            $this->applyOpts(Browsershot::html($html), $opts)->save($filepath);
        } catch (\Throwable $e) {
            Craft::$app->getLog()->getLogger()->log($e->getMessage() . ' ' . $e->getTraceAsString(), LogLevel::ERROR);
            if (Craft::$app->getConfig()->getGeneral()->devMode) {
                throw $e;
            }
        }

        return new ShootModel($filepath, $this->getUrl($filename));
    }


    /**
     * Screenshot an (external) URL
     *
     * @param string $url
     * @param array $opts
     * @return ShootModel
     * @throws \Throwable
     */
    public function url(string $url, array $opts = [])
    {

        /** @var Settings $settings */
        $settings = Shoot::$plugin->getSettings();

        $opts = array_merge([], $opts);
        $extension = ($opts['extension'] ?? $settings->defaultExtension ?: 'png');
        $filename = $opts['filename'] ?? $this->convertUrlToFilename($url);

        $filename = $this->buildFilename($filename, $extension, $opts);
        $filepath = $this->getPath($filename);

        if (\file_exists($filepath)) {
            return new ShootModel($filepath, $this->getUrl($filename));
        }

        try {
            $this->applyOpts(Browsershot::url($url), $opts)->save($filepath);
        } catch (\Throwable $e) {
            Craft::$app->getLog()->getLogger()->log($e->getMessage() . ' ' . $e->getTraceAsString(), LogLevel::ERROR);
            if (Craft::$app->getConfig()->getGeneral()->devMode) {
                throw $e;
            }
        }

        return new ShootModel($filepath, $this->getUrl($filename));
    }


    /**
     * Render and screenshot a template (needs to be under /templates/{path})
     *
     * @param string|array $paths
     * @param array $vars
     * @param array $opts
     * @param bool $devMode
     * @return mixed|string
     * @throws \Throwable
     */
    public function template($paths, array $vars = [], array $opts = [], bool $devMode = false)
    {

        // Set template mode to "Site"
        $view = Craft::$app->getView();
        $originalTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        if (!\is_array($paths)) {
            $paths = [$paths];
        }

        $templatePath = null;
        foreach ($paths as $path) {
            if ($view->doesTemplateExist($path)) {
                $templatePath = $path;
                break;
            }
        }

        if (!$templatePath) {
            throw new \Exception(Craft::t('No template found'));
        }

        // Turn off devMode before rendering
        $originalDevMode = Craft::$app->getConfig()->getGeneral()->devMode;
        Craft::$app->getConfig()->getGeneral()->devMode = $devMode;

        try {
            $html = $view->renderTemplate($path, $vars);
        } catch (\Throwable $e) {
            Craft::$app->getLog()->getLogger()->log($e->getMessage() . ' ' . $e->getTraceAsString(), LogLevel::ERROR);
            if ($originalDevMode) {
                throw $e;
            }
        }

        // Reset devMode to original value
        Craft::$app->getConfig()->getGeneral()->devMode = $originalDevMode;

        // Reset the template mode
        $view->setTemplateMode($originalTemplateMode);

        // Append the template path to the filename
        if (!isset($opts['filename'])) {
            $opts['filename'] = \str_replace('/', '-', $templatePath) . '-' . \md5($html);
        }

        return $this->html($html, $opts);
    }

    /**
     * Gets absolute URL for a screenshot filename
     *
     * @param $filename
     * @return mixed|string
     */
    public function getUrl($filename): string
    {
        /** @var Settings $settings */
        $settings = Shoot::$plugin->getSettings();
        $shootPath = $settings->baseUrl ?: '/shoot';
        return $this->fixSlashes($shootPath . '/' . $filename);
    }

    /**
     * Gets absolute filepath for a screenshot filename
     *
     * @param $filename
     * @return string
     */
    public function getPath($filename): string
    {
        return $this->getFilesystemPath() . $filename;
    }


    /**
     * Prepares an HTML string for screenshot
     *
     * @param string $input
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    protected function prepareHtml(string $input): string
    {

        // First, convert any locally hosted images to base64 because that's safer
        \libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($input, 'HTML-ENTITIES', $opts['encoding'] ?? 'UTF-8'));
        \libxml_use_internal_errors(false);

        $imageTags = $dom->getElementsByTagName('img');

        /** @var \DOMElement $imgTag */
        foreach ($imageTags as $imgTag) {
            $src = $imgTag->getAttribute('src');
            if ($this->isLocalResource($src)) {
                $srcPath = $this->getResourcePathFromUrl($src);
                if (!$srcPath) {
                    continue;
                }
                $model = new ShootModel($srcPath, $src);
                $base64 = $model->getDataUri();
                if (!$base64) {
                    continue;
                }
                $imgTag->setAttribute('src', $base64);
            }
        }

        // Then, inline any local, external CSS resources
        $linkTags = $dom->getElementsByTagName('link');

        /** @var \DOMElement $linkTag */
        foreach ($linkTags as $linkTag) {

            $href = $linkTag->getAttribute('href');

            if (!$href || !strlen($href)) {
                continue;
            }

            $extension = \strtolower(pathinfo($href, PATHINFO_EXTENSION));

            if ($extension !== 'css' && $linkTag->getAttribute('rel') !== 'stylesheet') {
                continue;
            }

            $filepath = $this->getResourcePathFromUrl($href);

            if (!\file_exists($filepath)) {
                continue;
            }

            $css = \file_get_contents($filepath);
            if (!$css || !\strlen($css)) {
                continue;
            }

            // Create a <style> tag to contain the inlined CSS
            $styleTag = $dom->createElement('style', $css);
            $linkTag->parentNode->replaceChild($styleTag, $linkTag);
        }

        // ...and, any local JS resources
        $scriptTags = $dom->getElementsByTagName('script');
        /** @var \DOMElement $scriptTag */
        foreach ($scriptTags as $scriptTag) {

            $src = $scriptTag->getAttribute('src');

            if (!$src || !\strlen($src)) {
                continue;
            }

            $filepath = $this->getResourcePathFromUrl($src);

            if (!\file_exists($filepath)) {
                continue;
            }

            $js = \file_get_contents($filepath);
            if (!$js || !\strlen($js)) {
                continue;
            }

            $scriptTag->removeAttribute('src');
            $script = $dom->createDocumentFragment();
            $script->appendXML($js);
            $scriptTag->appendChild($script);
        }

        return $dom->saveHTML() ?: $input;
    }

    /**
     * @param string $url
     * @return mixed|null|string
     */
    protected function getResourcePathFromUrl(string $url)
    {
        $path = \parse_url($url, PHP_URL_PATH);
        if (!$path || !\strlen($path)) {
            return null;
        }
        /** @var Settings $settings */
        $settings = Shoot::$plugin->getSettings();
        $pubRoot = $settings->publicRoot ?: $_SERVER['DOCUMENT_ROOT'] ?? '';
        $filePath = $this->fixSlashes($pubRoot . '/' . $path);
        return \file_exists($filePath) ? $filePath : null;
    }

    /**
     * Fixes slashes in path
     *
     * @param            $str
     * @param bool|false $removeInitial
     * @param bool|false $removeTrailing
     *
     * @return mixed|string
     */
    protected function fixSlashes($str, $removeInitial = false, $removeTrailing = false): string
    {
        $str = \preg_replace('/([^:])(\/{2,})/', '$1/', $str);
        if ($removeInitial) {
            $str = \ltrim($str, '/');
        }
        if ($removeTrailing) {
            $str = \rtrim($str, '/');
        }
        return $str;
    }

    /**
     * @param $url
     * @return bool
     */
    protected function isLocalResource($url): bool
    {
        $siteHost = \parse_url(Craft::$app->getConfig()->getGeneral()->siteUrl, PHP_URL_HOST);
        $urlHost = \parse_url($url, PHP_URL_HOST);
        return !!($urlHost === null || $urlHost === $siteHost);
    }

    /**
     * @return string
     */
    protected function getFilesystemPath(): string
    {
        /** @var Settings $settings */
        $settings = Shoot::$plugin->getSettings();
        $path = rtrim($settings->systemPath ?: $_SERVER['DOCUMENT_ROOT'] . '/shoot', '/') . '/';
        if (!\file_exists($path) || !\is_dir($path)) {
            \mkdir($path);
        }
        return $path;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function convertUrlToFilename(string $url): string
    {
        $filename = \mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $url);
        return \mb_ereg_replace("([\.]{2,})", '', $filename);
    }

    /**
     * @param string $basename
     * @param string $extension
     * @param array $opts
     * @return string
     */
    protected function buildFilename(string $basename, string $extension, array $opts = [])
    {
        return \implode('-', \array_filter([$basename, $this->optsToString($opts)])) . '.' . $extension;
    }

    /**
     * @param array $opts
     * @param string $glue
     * @return string
     */
    protected function optsToString(array $opts = [], $glue = '-'): string
    {
        $ret = '';

        foreach ($opts as $key => $value) {

            if ($key === 'filename') {
                continue;
            }

            if (\is_array($value)) {
                $ret .= $this->optsToString($value, $glue) . $glue;
            } else {
                $ret .= ((string)$value) . $glue;
            }
        }

        $ret = \substr($ret, 0, 0 - \strlen($glue));

        return $ret;
    }


    /**
     * @param Browsershot $shot
     * @param array $opts
     * @return Browsershot
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     */
    protected function applyOpts(Browsershot $shot, array $opts = []): Browsershot
    {

        // Set node_modules path
        $nodeModulesPath = $this->fixSlashes(Craft::$app->getVendorPath() . '/vaersaagod/shoot/node_modules');
        if (!\file_exists($nodeModulesPath) || !\is_dir($nodeModulesPath)) {
            throw new \Exception("node_modules folder not found – please run Yarn to install dependencies");
        }
        $shot->setNodeModulePath($nodeModulesPath);

        // Custom Chromium path?
        /** @var Settings $settings */
        $settings = Shoot::$plugin->getSettings();
        if ($settings->chromiumPath) {
            $shot->setChromePath($settings->chromiumPath);
        }

            // Viewport size
        $viewportWidth = $opts['viewport'][0] ?? 800;
        $viewportHeight = $opts['viewport'][1] ?? 600;
        $shot->windowSize($viewportWidth, $viewportHeight);

        // Clip
        $clip = $opts['clip'] ?? null;
        if ($clip) {
            $shot->clip($clip[0] ?? 0, $clip[1] ?? 0, $clip[2] ?? $viewportWidth, $clip[3] ?? $viewportHeight);
        }

        // Transform
        $transform = $opts['transform'] ?? null;
        if ($transform) {
            // TODO add "mode" parameter
            $shot->fit(Manipulations::FIT_CONTAIN, $transform[0] ?? $viewportWidth, $transform[1] ?? $viewportHeight);
        }

        // Retina?
        if ($opts['retina'] ?? null) {
            $shot->deviceScaleFactor(2);
        } else if ($opts['deviceScale'] ?? null) {
            $shot->deviceScaleFactor($opts['deviceScale']);
        }

        // Simulate mobile device?
        if (($opts['mobile'] ?? null) === true) {
            $shot->mobile();
        }

        // Simulate touch device?
        if (($opts['touch'] ?? null) === true) {
            $shot->touch();
        }

        // User agent
        if ($opts['userAgent'] ?? null) {
            $shot->userAgent($opts['userAgent']);
        }

        // Hide background
        if (($opts['hideBackground'] ?? null) === true) {
            $shot->hideBackground();
        }

        // Full page?
        if (($opts['fullPage'] ?? null) === true) {
            $shot->fullPage();
        }

        // Delay
        $delay = $opts['delay'] ?? null;
        if ($delay) {
            $shot->setDelay($delay);
        }

        $shot->waitUntilNetworkIdle();

        // Dismiss dialogs
        $shot->dismissDialogs();

        // Disable sandbox and https errors
        $shot->noSandbox();
        $shot->ignoreHttpsErrors();

        return $shot;
    }
}

<?php
/**
 * Shoot plugin for Craft CMS 3.x
 *
 * Screenshot stuff.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2019 Værsågod
 */

namespace vaersaagod\shoot\controllers;

use vaersaagod\shoot\Shoot;

use Craft;
use craft\helpers\FileHelper;
use craft\web\Controller;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * @author    Værsågod
 * @package   Shoot
 * @since     2.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['serve'];

    // Public Methods
    // =========================================================================


    /**
     * @return bool
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionServe()
    {
        $request = Craft::$app->getRequest();
        $filename = $request->getParam('file') ?: $request->getBodyParam('file') ?: null;

        if (!$filename) {
            throw new BadRequestHttpException("No filename");
        }

        $filepath = Shoot::$plugin->shoot->getPath($filename);

        if (!\file_exists($filepath)) {
            throw new NotFoundHttpException("{$filename} does not exist");
        }

        $file = \file_get_contents($filepath);
        if (!$file) {
            throw new \Exception("{$filename} could not be read");
        }

        // Download file?
        $doDownload = ($request->getParam('download') ?? $request->getBodyParam('download')) !== null;
        if ($doDownload) {
            Yii::$app->response->sendFile($filepath, $filename);
            return true;
        }

        $fileSize = \filesize($filepath);
        $mimeType = FileHelper::getMimeType($filepath);

        \header("Content-Type: {$mimeType}");
        \header("Content-Length: {$fileSize}");

        $fp = \fopen($filepath, 'rb');
        \fpassthru($fp);

        return true;
    }
}

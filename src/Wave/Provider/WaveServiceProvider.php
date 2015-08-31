<?php

/**
 * Service provider for the Wave addon.
 *
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Wave\Provider;

use GuzzleHttp\Client as HttpClient;

use Silex\Application;
use Silex\ServiceProviderInterface;

use skyflow\Controller\OAuthController;

use Salesforce\Authenticator\SalesforceAuthenticator;
use Salesforce\Domain\SalesforceUser;

use Wave\Controller\AuthController;
use Wave\Controller\HelperController;
use Wave\DAO\WaveRequestDAO;
use Wave\Domain\WaveRequest;
use Wave\Form\Type\WaveCredentialsType;
use Wave\Service\AuthService;
use Wave\Service\WaveService;

/**
 * Service provider for the Wave addon.
 */
class WaveServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['wave.authenticator'] = $app->share(function () use ($app) {
            $user = $app['wave.user'];

            $sandbox = $user->getIsSandbox();
            $loginUrl = null;
            if ($sandbox) {
                $loginUrl = 'https://test.salesforce.com';
            } else {
                $loginUrl = 'https://login.salesforce.com';
            }

            // code must be defined later in the AuthController callback action
            return new SalesforceAuthenticator(array(
                'login_url'     => $loginUrl,
                'response_type' => 'code',
                'grant_type'    => 'code',
                'client_id'     => $user->getClientId(),
                'client_secret' => $user->getClientSecret(),
                'redirect_uri'  => 'https://' . $_SERVER['HTTP_HOST'] . '/wave/auth/callback',
                'code'          => null,
                'instance_url'  => $user->getInstanceUrl(),
                'refresh_token' => $user->getRefreshToken()
            ));
        });

        $app['wave.controller.helper'] = $app->share(function () use ($app) {
            return new WaveHelperController(
                $app['request'],
                $app['wave'],
                $app['wave.form.query']
            );
        });

        $app['wave.controller.oauth'] = $app->share(function () use ($app) {
            return new OAuthController(
                $app['request'],
                $app['salesforce.oauth'],
                '/salesforce/auth'
            );
        });

        $app['wave.user'] = $app->share(function () use ($app) {
            $app['wave.user.dao']->findBySkyflowUserId();
        });

        $app['wave.user.dao'] = $app->share(function () use ($app) {
            return new SalesforceUserDAO($app['db']);
        });

        $app['dao.wave_request'] = $app->share(function () use ($app) {
            return new WaveRequestDAO($app['db']);
        });

        $app['wave.form.type.credentials'] = $app->share(function () use ($app) {
            return new WaveCredentialsType($app['wave.user']);
        });

        $app['wave.form.credentials'] = function () use ($app) {
            return $app['form.factory']->create($app['wave.form.type.credentials']);
        };

        $app['wave.auth'] = $app->share(function () use ($app) {
            return new AuthService(
                $app['wave.authenticator'],
                $app['user'],
                $app['dao.user']
            );
        });

        $app['wave'] = $app->share(function () use ($app) {
            return new WaveService(
                $app['user'],
                new HttpClient()
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
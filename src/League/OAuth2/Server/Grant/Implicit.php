<?php
/**
 * OAuth 2.0 implicit grant
 *
 * @package     php-loep/oauth2-server
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) 2013 PHP League of Extraordinary Packages
 * @license     http://mit-license.org/
 * @link        http://github.com/php-loep/oauth2-server
 */

namespace League\OAuth2\Server\Grant;

use League\OAuth2\Server\Request;
use League\OAuth2\Server\Authorization;
use League\OAuth2\Server\Exception;
use League\OAuth2\Server\Util\SecureKey;
use League\OAuth2\Server\Storage\SessionInterface;
use League\OAuth2\Server\Storage\ClientInterface;
use League\OAuth2\Server\Storage\ScopeInterface;

/**
 * Client credentials grant class
 */
class Implicit implements GrantTypeInterface {

    /**
     * Grant identifier
     * @var string
     */
    protected $identifier = 'implicit';

    /**
     * Response type
     * @var string
     */
    protected $responseType = 'token';

    /**
     * AuthServer instance
     * @var AuthServer
     */
    protected $authServer = null;

    /**
     * Access token expires in override
     * @var int
     */
    protected $accessTokenTTL = null;

    /**
     * Constructor
     * @param Authorization $authServer Authorization server instance
     * @return void
     */
    public function __construct(Authorization $authServer)
    {
        $this->authServer = $authServer;
    }

    /**
     * Return the identifier
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Return the response type
     * @return string
     */
    public function getResponseType()
    {
        return $this->responseType;
    }

    /**
     * Override the default access token expire time
     * @param int $accessTokenTTL
     * @return void
     */
    public function setAccessTokenTTL($accessTokenTTL)
    {
        $this->accessTokenTTL = $accessTokenTTL;
    }

    /**
     * Complete the client credentials grant
     * @param  null|array $inputParams
     * @return array
     */
    public function completeFlow($authParams = null)
    {
        // Remove any old sessions the user might have
        $this->authServer->getStorage('session')->deleteSession($authParams['client_id'], 'user', $authParams['user_id']);

        // Generate a new access token
        $accessToken = SecureKey::make();

        // Compute expiry time
        $accessTokenExpiresIn = ($this->accessTokenTTL !== null) ? $this->accessTokenTTL : $this->authServer->getAccessTokenTTL();
        $accessTokenExpires = time() + $accessTokenExpiresIn;

        // Create a new session
        $sessionId = $this->authServer->getStorage('session')->createSession($authParams['client_id'], 'user', $authParams['user_id']);

        // Create an access token
        $accessTokenId = $this->authServer->getStorage('session')->associateAccessToken($sessionId, $accessToken, $accessTokenExpires);

        // Associate scopes with the access token
        foreach ($authParams['scopes'] as $scope) {
            $this->authServer->getStorage('session')->associateScope($accessTokenId, $scope['id']);
        }

        $response = array(
            'access_token'  =>  $accessToken,
            'token_type'    =>  'Bearer',
            'expires'       =>  $accessTokenExpires,
            'expires_in'    =>  $accessTokenExpiresIn,
        );

        return $response;
    }

}

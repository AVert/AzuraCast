<?php
namespace App;

class Csrf
{
    /**
     * @var \App\Session\Instance
     */
    protected $_session;

    /**
     * @var int The length of the string to generate.
     */
    protected $_csrf_code_length = 10;

    /**
     * @var int The lifetime (in seconds) of an un-renewed CSRF token.
     */
    protected $_csrf_lifetime = 3600;

    /**
     * @var string The default namespace used for token generation.
     */
    protected $_csrf_default_namespace = 'general';

    public function __construct(Session $session)
    {
        $this->_session = $session->get('csrf');
    }

    /**
     * Generate a Cross-Site Request Forgery (CSRF) protection token.
     * The "namespace" allows distinct CSRF tokens for different site functions,
     * while not crowding the session namespace with one token for each action.
     *
     * If not renewed (with another "generate" call to the same namespace),
     * a CSRF token will last the time specified in $this->_csrf_lifetime.
     *
     * @param string $namespace
     * @return null|String
     */
    public function generate($namespace = null)
    {
        if ($namespace === null)
            $namespace = $this->_csrf_default_namespace;

        $key = NULL;
        if (isset($this->_session[$namespace]))
        {
            $key = $this->_session[$namespace]['key'];
            if (strlen($key) !== $this->_csrf_code_length)
                $key = NULL;
        }

        if (!$key)
            $key = $this->randomString($this->_csrf_code_length);

        $this->_session[$namespace] = array(
            'key'       => $key,
            'timestamp' => time(),
        );

        return $key;
    }

    /**
     * Verify a supplied CSRF token against the tokens stored in the session.
     *
     * @param $key
     * @param string $namespace
     * @return array
     */
    public function verify($key, $namespace = null)
    {
        if ($namespace === null)
            $namespace = $this->_csrf_default_namespace;

        if (empty($key))
            return array('is_valid' => false, 'message' => 'A CSRF token is required for this request.');

        if (strlen($key) !== $this->_csrf_code_length)
            return array('is_valid' => false, 'message' => 'Malformed CSRF token supplied.');

        if (!isset($this->_session[$namespace]))
            return array('is_valid' => false, 'message' => 'No CSRF token supplied for this namespace.');

        $namespace_info = $this->_session[$namespace];

        if (strcmp($key, $namespace_info['key']) !== 0)
            return array('is_valid' => false, 'message' => 'Invalid CSRF token supplied.');

        // Compare against time threshold (CSRF keys last 60 minutes).
        $threshold =  $namespace_info['timestamp']+$this->_csrf_lifetime;

        if (time() >= $threshold)
            return array('is_valid' => false, 'message' => 'This CSRF token has expired!');

        return array('is_valid' => true);
    }

    /**
     * Wrapper to the verify() function that triggers an exception for invalid tokens.
     *
     * @param $key
     * @param null $namespace
     * @return bool
     * @throws Exception
     */
    public function requireValid($key, $namespace = null)
    {
        $verify_result = $this->verify($key, $namespace);

        if ($verify_result['is_valid'])
            return true;
        else
            throw new Exception('Cannot validate CSRF token: '.$verify_result['message']);
    }

    /**
     * Generates a random string of given $length.
     *
     * @param Integer $length The string length.
     * @return String The randomly generated string.
     */
    public function randomString($length)
    {
        $seed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijqlmnopqrtsuvwxyz0123456789';
        $max = strlen( $seed ) - 1;

        $string = '';
        for ( $i = 0; $i < $length; ++$i )
            $string .= $seed{intval( mt_rand( 0.0, $max ) )};

        return $string;
    }
}
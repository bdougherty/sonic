<?php
use Sonic\Config, Sonic\App;
use Sonic\UnitTest\TestCase;

class ConfigTest extends TestCase
{
    public function testConstruct()
    {
        $app = App::getInstance();
        $path = $app->getPath('configs') . '/app.ini';
        $config = new Config($path, 'dev');
        $this->isTrue($config instanceof Config);

        // no parents
        $config = new Config($app->getPath('configs') . '/app2.ini', 'global');
        $this->isTrue($config instanceof Config);

        // test php based config
        $config = new Config($app->getPath('configs') . '/app.php', 'user', Config::PHP);
        $this->isTrue($config instanceof Config);
    }

    public function testBadEnvironment()
    {
        $this->isException('Sonic\Exception');
        $app = App::getInstance();
        $config = new Config($app->getPath('configs') . '/app.ini', 'made up environment');
    }

    public function testBadPath()
    {
        $this->isException('Sonic\Exception');
        $config = new Config('/bad/path', App::getInstance()->getEnvironment());
    }

    public function testGet()
    {
        $app = App::getInstance();
        $path = $app->getPath('configs') . '/app.ini';
        $config = new Config($path, 'production');
        $var = $config->get('made_up_var');
        $this->isNull($var);

        $analytics = $config->get('use_analytics');
        $this->isEqual($analytics, 1);

        $debug = $config->get('debug');
        $this->isEqual($debug, 0);

        $url = $config->get('urls', 'www');
        $this->isEqual($url, 'http://www.website.com');

        $config = new Config($path, 'user');
        $debug = $config->get('debug');
        $this->isEqual($debug, 1);

        $url = $config->get('urls', 'www');
        $this->isEqual($url, 'http://user.website.local');

        $urls = $config->get('urls');
        $this->isArray($urls);
        $this->isEqual(count($urls), 1);
    }
}

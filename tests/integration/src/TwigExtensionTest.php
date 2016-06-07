<?php

namespace Tests\Integration;

use Arachne\Bootstrap\Configurator;
use Codeception\Test\Unit;
use Tracy\Dumper;
use Twig_Environment;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class TwigExtensionTest extends Unit
{
    public function testConfiguration()
    {
        $container = $this->createContainer('config.neon');

        /* @var $twig Twig_Environment */
        $twig = $container->getByType(Twig_Environment::class);
        $this->assertInstanceOf(Twig_Environment::class, $twig);

        // Fix dump result comparison on linux.
        Dumper::$terminalColors = null;

        $this->assertSame('"value" (5)', trim($twig->render('index.twig', [
            'foo' => 'value',
        ])));

        $this->assertSame('"long_val ... " (10)', trim($twig->render('@namespace/index.twig', [
            'bar' => 'long_value',
        ])));
    }

    private function createContainer($file)
    {
        $config = new Configurator();
        $config->setTempDirectory(TEMP_DIR);
        $config->addConfig(__DIR__ . '/../config/' . $file);
        return $config->createContainer();
    }
}

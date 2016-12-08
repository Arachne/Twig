<?php

namespace Tests\Integration;

use Codeception\Test\Unit;
use DateTime;
use Tracy\Dumper;
use Twig_Environment;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class TwigExtensionTest extends Unit
{
    public function testConfiguration()
    {
        /* @var $twig Twig_Environment */
        $twig = $this->tester->grabService(Twig_Environment::class);
        $this->assertInstanceOf(Twig_Environment::class, $twig);

        // Fix dump result comparison on linux.
        Dumper::$terminalColors = null;

        $this->assertSame('"value" (5)', trim($twig->render('index.twig', [
            'foo' => 'value',
        ])));

        $this->assertSame('"long_val ... " (10)', trim($twig->render('@namespace/index.twig', [
            'bar' => 'long_value',
        ])));

        $this->assertInstanceOf(DateTime::class, $twig->getRuntime('DateTime'));
    }
}

<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\UI\Implementation as I;
use ILIAS\UI\Component\Popover\Popover;

require_once(__DIR__ . "/../../../../../../vendor/composer/vendor/autoload.php");
require_once(__DIR__ . "/../../Base.php");

/**
 * Class PopoverTest
 *
 * Tests on the Popover component implementation
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class PopoverTest extends ILIAS_UI_TestBase
{
    public function getFactory(): NoUIFactory
    {
        return new class () extends NoUIFactory {
            public function legacy(): I\Component\Legacy\Factory
            {
                return new I\Component\Legacy\Factory(new I\Component\SignalGenerator());
            }
        };
    }

    public function testImplementsInterface(): void
    {
        $factory = new I\Component\Popover\Factory(new I\Component\SignalGenerator());
        $standard = $factory->standard(new DummyComponent());
        $this->assertInstanceOf("ILIAS\\UI\\Component\\Popover\\Standard", $standard);
        $listing = $factory->listing([new DummyComponent()]);
        $this->assertInstanceOf("ILIAS\\UI\\Component\\Popover\\Listing", $listing);
    }

    public function testThatPositionIsAutoByDefault(): void
    {
        $factory = new I\Component\Popover\Factory(new I\Component\SignalGenerator());
        $popover = $factory->standard(new DummyComponent());
        $this->assertEquals(Popover::POS_AUTO, $popover->getPosition());
    }

    public function testWithPosition(): void
    {
        $factory = new I\Component\Popover\Factory(new I\Component\SignalGenerator());
        $popover1 = $factory->standard(new DummyComponent());
        $popover2 = $popover1->withVerticalPosition();
        $popover3 = $popover2->withHorizontalPosition();
        $this->assertEquals(Popover::POS_AUTO, $popover1->getPosition());
        $this->assertEquals(Popover::POS_VERTICAL, $popover2->getPosition());
        $this->assertEquals(Popover::POS_HORIZONTAL, $popover3->getPosition());
        $this->assertEquals($popover1->getContent(), $popover2->getContent());
        $this->assertEquals($popover1->getContent(), $popover3->getContent());
    }

    public function testRenderStandard(): void
    {
        $factory = new I\Component\Popover\Factory(new I\Component\SignalGenerator());
        $popover = $factory->standard($this->getFactory()->legacy()->content('myContent'));
        $expected = $this->normalizeHTML($this->getExpectedStandardHTML('myContent'));
        $actual = $this->normalizeHTML($this->getDefaultRenderer()->render($popover));
        $this->assertEquals($expected, $actual);
    }

    public function testRenderListing(): void
    {
        // TODO Listing not yet in framework core
        $this->assertTrue(true);
    }

    public function testRenderAsync(): void
    {
        $factory = new I\Component\Popover\Factory(new I\Component\SignalGenerator());
        $popover = $factory->standard($this->getFactory()->legacy()->content('myContent'))->withAsyncContentUrl('/blub/');
        $this->assertEquals('', $this->getDefaultRenderer()->render($popover));
    }

    protected function getExpectedStandardHTML(string $content): string
    {
        return '<div class="il-standard-popover-content" style="display:none;" id="id_1">' . $content . '</div>';
    }
}

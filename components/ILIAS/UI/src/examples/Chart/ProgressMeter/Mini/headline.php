<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Chart\ProgressMeter\Mini;

/**
 * ---
 * description: >
 *   Example for rendering a mini Progress Meter as part of a headline
 *
 * expected output: >
 *   ILIAS shows a rounded progress meter with a red colored bar and the headline "Your Progress". The bar takes up
 *   three quarter of the display.
 * ---
 */
function headline()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Genarating and rendering the mini progressmeter
    $progressmeter = $f->chart()->progressMeter()->mini(100, 75);

    // render
    return '<h3 style="display: inline-block;">Your Progress: </h3><div style="display: inline-block; padding-left: 20px">' .
        $renderer->render($progressmeter) . '</div>';
}

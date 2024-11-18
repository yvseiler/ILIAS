<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\ColorPicker;

/**
 * ---
 * description: >
 *   Base example showing how to plug a colorpicker into a form
 *
 * expected output: >
 *   ILIAS shows the rendered Component.
 * ---
 */
function base()
{
    //Step 0: Declare dependencies
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $request = $DIC->http()->request();

    //Step 1: Define the input field
    $color_input = $ui->input()->field()->colorpicker("Color", "click to select a color");

    //Step 2: Define the form and attach the field.
    $form = $ui->input()->container()->form()->standard('#', ['color' => $color_input]);

    //Step 3: Define some data processing.
    $result = '';
    if ($request->getMethod() == "POST") {
        $form = $form->withRequest($request);
        $result = $form->getData();
    }

    //Step 4: Render the form/result.
    return
        "<pre>" . print_r($result, true) . "</pre><br/>" .
        $renderer->render($form);
}

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

declare(strict_types=0);

namespace ILIAS\Tracking\View\ProgressBlock;

use ilDBInterface;
use ILIAS\Tracking\View\ProgressBlock\Settings\FactoryInterface as SettingsFactoryInterface;
use ILIAS\Tracking\View\ProgressBlock\Settings\Factory as SettingsFactory;

class Factory implements FactoryInterface
{
    public function __construct(protected ilDBInterface $db)
    {
    }

    public function settings(): SettingsFactoryInterface
    {
        return new SettingsFactory($this->db);
    }
}

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

namespace ILIAS\Tracking\Setup;

use ilDatabaseUpdateStepsExecutedObjective;
use ilDatabaseUpdateStepsMetricsCollectedObjective;
use ILIAS\Setup\Agent\NullAgent;
use ILIAS\Setup\ObjectiveCollection;
use ILIAS\Setup\Objective;
use ILIAS\Setup\Config;
use ILIAS\Setup\Metrics\Storage;

class Agent extends NullAgent
{
    public function getUpdateObjective(Config $config = null): Objective
    {
        return new ObjectiveCollection(
            'Tracking',
            false,
            new ilDatabaseUpdateStepsExecutedObjective(new ProgressBlockUpdateSteps11())
        );
    }

    public function getStatusObjective(Storage $storage): Objective
    {
        return new ObjectiveCollection(
            'Tracking',
            true,
            new ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new ProgressBlockUpdateSteps11())
        );
    }
}

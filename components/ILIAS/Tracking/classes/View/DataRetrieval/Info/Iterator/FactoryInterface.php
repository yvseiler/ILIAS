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

namespace ILIAS\Tracking\View\DataRetrieval\Info\Iterator;

use ILIAS\Tracking\View\DataRetrieval\Info\Iterator\CombinedInterface as CombinedIteratorInterface;
use ILIAS\Tracking\View\DataRetrieval\Info\Iterator\LPInterface as LPIteratorInterface;
use ILIAS\Tracking\View\DataRetrieval\Info\Iterator\ObjectDataInterface as ObjectDataIteratorInterface;
use ILIAS\Tracking\View\DataRetrieval\Info\ObjectDataInterface as ObjectDataInfoInterface;
use ILIAS\Tracking\View\DataRetrieval\Info\LPInterface as LPInfoInterface;
use ILIAS\Tracking\View\DataRetrieval\Info\CombinedInterface as CombinedInfoInterface;

interface FactoryInterface
{
    public function combined(
        CombinedInfoInterface ...$infos
    ): CombinedIteratorInterface;

    public function lp(
        LPInfoInterface ...$infos
    ): LPIteratorInterface;

    public function objectData(
        ObjectDataInfoInterface ...$infos
    ): ObjectDataIteratorInterface;
}

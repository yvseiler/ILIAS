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

namespace ILIAS\Export;

/**
 * @author Alexander Killing <killing@leifos.de>
 */
class Service
{
    protected static array $instance = [];
    public function __construct()
    {
    }

    public function internal(): InternalService
    {
        return self::$instance["internal"] ??= new InternalService();
    }

    public function domain(): ExternalDomainService
    {
        return self::$instance["domain"] ??= new ExternalDomainService(
            $this->internal()->domain()
        );
    }

}

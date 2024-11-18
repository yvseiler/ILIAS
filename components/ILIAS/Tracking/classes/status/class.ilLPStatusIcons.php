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

/**
 * Caches and supplies the paths to the learning progress status images.
 * @author  Tim Schmitz <schmitz@leifos.de>
 */
class ilLPStatusIcons
{
    public const ICON_VARIANT_LONG = 0;
    public const ICON_VARIANT_SHORT = 1;
    public const ICON_VARIANT_SCORM = 2;
    public const ICON_VARIANT_DEFAULT = ilLPStatusIcons::ICON_VARIANT_LONG;

    private static ?self $instance_variant_long = null;
    private static ?self $instance_variant_short = null;
    private static ?self $instance_variant_scorm = null;

    private string $image_path_in_progress = '';
    private string $image_path_completed = '';
    private string $image_path_not_attempted = '';
    private string $image_path_failed = '';

    //The following two icons are not available as a long variant.
    private string $image_path_asset = '';
    private string $image_path_running = '';

    private \ILIAS\UI\Factory $factory;
    private \ILIAS\UI\Renderer $renderer;

    public static function getInstance(int $variant = ilLPStatusIcons::ICON_VARIANT_DEFAULT, ?\ILIAS\UI\Renderer $renderer = null, ?\ILIAS\UI\Factory $factory = null): ilLPStatusIcons
    {
        if (!$renderer || !$factory) {
            global $DIC;
            $renderer = $DIC->ui()->renderer();
            $factory = $DIC->ui()->factory();
        }

        switch ($variant) {
            case ilLPStatusIcons::ICON_VARIANT_SCORM:
                if (self::$instance_variant_scorm) {
                    return self::$instance_variant_scorm;
                }
                return self::$instance_variant_scorm = new self(ilLPStatusIcons::ICON_VARIANT_SCORM, $renderer, $factory);

            case ilLPStatusIcons::ICON_VARIANT_SHORT:
                if (self::$instance_variant_short) {
                    return self::$instance_variant_short;
                }
                return self::$instance_variant_short = new self(ilLPStatusIcons::ICON_VARIANT_SHORT, $renderer, $factory);

            case ilLPStatusIcons::ICON_VARIANT_LONG:
                if (self::$instance_variant_long) {
                    return self::$instance_variant_long;
                }
                return self::$instance_variant_long = new self(ilLPStatusIcons::ICON_VARIANT_LONG, $renderer, $factory);

            default:
                throw new ilLPException("No such variant of the LP icons exists.");
        }
    }

    private function __construct(int $variant, \ILIAS\UI\Renderer $renderer, \ILIAS\UI\Factory $factory)
    {
        $this->factory = $factory;
        $this->renderer = $renderer;

        switch ($variant) {
            case ilLPStatusIcons::ICON_VARIANT_SCORM:
                $this->image_path_in_progress = $this->buildImagePath('scorm/incomplete.svg');
                $this->image_path_completed = $this->buildImagePath('scorm/complete.svg');
                $this->image_path_not_attempted = $this->buildImagePath('scorm/not_attempted.svg');
                $this->image_path_failed = $this->buildImagePath('scorm/failed.svg');
                $this->image_path_asset = $this->buildImagePath('scorm/asset.svg');
                $this->image_path_running = $this->buildImagePath('scorm/running.svg');
                break;

            case ilLPStatusIcons::ICON_VARIANT_SHORT:
                $this->image_path_in_progress = $this->buildImagePath('learning_progress/short/in_progress.svg');
                $this->image_path_completed = $this->buildImagePath('learning_progress/short/completed.svg');
                $this->image_path_not_attempted = $this->buildImagePath('learning_progress/short/not_attempted.svg');
                $this->image_path_failed = $this->buildImagePath('learning_progress/short/failed.svg');
                $this->image_path_asset = $this->buildImagePath('learning_progress/short/asset.svg');
                $this->image_path_running = $this->buildImagePath('learning_progress/short/running.svg');
                break;

            case ilLPStatusIcons::ICON_VARIANT_LONG:
                $this->image_path_in_progress = $this->buildImagePath('learning_progress/in_progress.svg');
                $this->image_path_completed = $this->buildImagePath('learning_progress/completed.svg');
                $this->image_path_not_attempted = $this->buildImagePath('learning_progress/not_attempted.svg');
                $this->image_path_failed = $this->buildImagePath('learning_progress/failed.svg');
                break;

            default:
                throw new ilLPException("No such variant of the LP icons exists.");
        }
    }

    protected function buildImagePath(string $image_name): string
    {
        return ilUtil::getImagePath($image_name);
    }

    public function getImagePathInProgress(): string
    {
        return $this->image_path_in_progress;
    }

    public function getImagePathCompleted(): string
    {
        return $this->image_path_completed;
    }

    public function getImagePathNotAttempted(): string
    {
        return $this->image_path_not_attempted;
    }

    public function getImagePathFailed(): string
    {
        return $this->image_path_failed;
    }

    /**
     * A long variant of this icon is not available.
     */
    public function getImagePathAsset(): string
    {
        if ($this->image_path_asset) {
            return $this->image_path_asset;
        }
        throw new ilLPException("A long variant of the 'asset' LP icon does not exist.");
    }

    /**
     * A long variant of this icon is not available.
     */
    public function getImagePathRunning(): string
    {
        if ($this->image_path_running) {
            return $this->image_path_running;
        }
        throw new ilLPException("A long variant of the 'running' LP icon does not exist.");
    }

    public function renderIcon(string $path, string $alt): string
    {
        if ($this === self::$instance_variant_scorm) {
            throw new ilLPException("SCORM variants of the LP icons cannot be rendered.");
        }

        return $this->renderer->render($this->getIconComponent($path, $alt));
    }

    public function getIconComponent(string $path, string $alt): \ILIAS\UI\Component\Symbol\Icon\Custom
    {
        return $this->factory->symbol()->icon()->custom($path, $alt, \ILIAS\UI\Component\Symbol\Icon\Icon::SMALL);
    }

    /**
     * @todo Check whether the default can be replaced by an exception.
     */
    public function getImagePathForStatus(int $a_status): string
    {
        switch ($a_status) {
            case ilLPStatus::LP_STATUS_IN_PROGRESS_NUM:
                return $this->getImagePathInProgress();

            case ilLPStatus::LP_STATUS_COMPLETED_NUM:
                return $this->getImagePathCompleted();

            case ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM:
                return $this->getImagePathNotAttempted();

            case ilLPStatus::LP_STATUS_FAILED_NUM:
                return $this->getImagePathFailed();

            default:
                return $this->getImagePathNotAttempted();
        }
    }

    /**
     * Returns the rendered icon with alt text.
     */
    public function renderIconForStatus(int $a_status, ?ilLanguage $a_lng = null): string
    {
        return $this->renderIcon(
            $this->getImagePathForStatus($a_status),
            ilLearningProgressBaseGUI::_getStatusText($a_status, $a_lng)
        );
    }

    /**
     * Transforms the string constants for the status to their interger equivalent.
     */
    public function lookupNumStatus(string $a_status): int
    {
        switch ($a_status) {
            case ilLPStatus::LP_STATUS_IN_PROGRESS:
                return ilLPStatus::LP_STATUS_IN_PROGRESS_NUM;

            case ilLPStatus::LP_STATUS_COMPLETED:
                return ilLPStatus::LP_STATUS_COMPLETED_NUM;

            case ilLPStatus::LP_STATUS_NOT_ATTEMPTED:
                return ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;

            case ilLPStatus::LP_STATUS_FAILED:
                return ilLPStatus::LP_STATUS_FAILED_NUM;

            default:
                throw new ilLPException("Not a valid status");
        }
    }
}

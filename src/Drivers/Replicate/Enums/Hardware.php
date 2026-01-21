<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Enums;

/**
 * Available hardware SKUs for Replicate deployments and model runs.
 */
enum Hardware: string
{
    case Cpu = 'cpu';
    case GpuA100Large = 'gpu-a100-large';
    case GpuA100Large2x = 'gpu-a100-large-2x';
    case GpuH100 = 'gpu-h100';
    case GpuL40s = 'gpu-l40s';
    case GpuL40s2x = 'gpu-l40s-2x';
    case GpuT4 = 'gpu-t4';
}

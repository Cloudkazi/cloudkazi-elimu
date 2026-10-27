<?php

namespace App\Services;

class SchoolLimitService
{
    public static function canCreate(int $schoolId, string $resource): bool
    {
        return PopulationAllocationService::canCreate($schoolId, $resource);
    }

    public static function remaining(int $schoolId, string $resource): ?int
    {
        return PopulationAllocationService::getRemaining($schoolId, $resource);
    }

    public static function limit(int $schoolId, string $resource): ?int
    {
        return PopulationAllocationService::getLimit($schoolId, $resource);
    }

    public static function usage(int $schoolId, string $resource): int
    {
        return PopulationAllocationService::getUsage($schoolId, $resource);
    }

    public static function invalidateCache(int $schoolId): void
    {
        PopulationAllocationService::invalidateCache($schoolId);
    }
}

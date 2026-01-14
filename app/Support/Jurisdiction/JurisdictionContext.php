<?php

namespace App\Support\Jurisdiction;

use JsonSerializable;

/**
 * DTO describing the user's jurisdiction window.
 * Backward-compatible with your previous version.
 */
final class JurisdictionContext implements JsonSerializable
{
    public const LEVEL_GLOBAL   = 'global';
    public const LEVEL_DIVISION = 'division';
    public const LEVEL_DISTRICT = 'district';
    public const LEVEL_CLUSTER  = 'cluster';
    public const LEVEL_NONE     = 'none';

    /** 'global' | 'division' | 'district' | 'cluster' | 'none' */
    public string $level = self::LEVEL_NONE;

    /** Optional: tenant context for company-scoped tables (clusters/admins) */
    public ?int $company_id  = null;

    public ?int $division_id = null;
    public ?int $district_id = null;
    public ?int $cluster_id  = null;

    public function __construct(
        string $level = self::LEVEL_NONE,
        ?int $divisionId = null,
        ?int $districtId = null,
        ?int $clusterId = null,
        ?int $companyId = null
    ) {
        $this->level       = $this->normalizeLevel($level);
        $this->division_id = $divisionId;
        $this->district_id = $districtId;
        $this->cluster_id  = $clusterId;
        $this->company_id  = $companyId;
        $this->normalize();
    }

    // ---- Factories ----
    public static function global(?int $companyId = null): self
    { return new self(self::LEVEL_GLOBAL, null, null, null, $companyId); }

    public static function division(int $divisionId, ?int $companyId = null): self
    { return new self(self::LEVEL_DIVISION, $divisionId, null, null, $companyId); }

    public static function district(int $districtId, ?int $divisionId = null, ?int $companyId = null): self
    { return new self(self::LEVEL_DISTRICT, $divisionId, $districtId, null, $companyId); }

    public static function cluster(int $clusterId, ?int $districtId = null, ?int $divisionId = null, ?int $companyId = null): self
    { return new self(self::LEVEL_CLUSTER, $divisionId, $districtId, $clusterId, $companyId); }

    public static function none(): self
    { return new self(self::LEVEL_NONE); }

    // ---- Predicates ----
    public function isGlobal(): bool   { return $this->level === self::LEVEL_GLOBAL; }
    public function isDivision(): bool { return $this->level === self::LEVEL_DIVISION; }
    public function isDistrict(): bool { return $this->level === self::LEVEL_DISTRICT; }
    public function isCluster(): bool  { return $this->level === self::LEVEL_CLUSTER; }
    public function isNone(): bool     { return $this->level === self::LEVEL_NONE; }

    // ---- Immutable-style helpers ----
    public function withCompany(?int $companyId): self
    { $c = clone $this; $c->company_id = $companyId; return $c; }

    public function withDivision(?int $divisionId): self
    { $c = clone $this; $c->division_id = $divisionId; $c->normalize(); return $c; }

    public function withDistrict(?int $districtId): self
    { $c = clone $this; $c->district_id = $districtId; $c->normalize(); return $c; }

    public function withCluster(?int $clusterId): self
    { $c = clone $this; $c->cluster_id = $clusterId; $c->normalize(); return $c; }

    // ---- Serialization ----
    public function toArray(): array
    {
        return [
            'level'       => $this->level,
            'company_id'  => $this->company_id,
            'division_id' => $this->division_id,
            'district_id' => $this->district_id,
            'cluster_id'  => $this->cluster_id,
        ];
    }

    public function jsonSerialize(): array
    { return $this->toArray(); }

    // ---- Internals ----
    private function normalizeLevel(string $level): string
    {
        $level = strtolower(trim($level));
        return in_array($level, [
            self::LEVEL_GLOBAL,
            self::LEVEL_DIVISION,
            self::LEVEL_DISTRICT,
            self::LEVEL_CLUSTER,
            self::LEVEL_NONE,
        ], true) ? $level : self::LEVEL_NONE;
    }

    /**
     * Keep ids consistent with the chosen level.
     */
    private function normalize(): void
    {
        switch ($this->level) {
            case self::LEVEL_GLOBAL:
                $this->division_id = null;
                $this->district_id = null;
                $this->cluster_id  = null;
                break;
            case self::LEVEL_DIVISION:
                $this->district_id = null;
                $this->cluster_id  = null;
                break;
            case self::LEVEL_DISTRICT:
                $this->cluster_id = null;
                break;
            case self::LEVEL_CLUSTER:
                // keep as-is (division/district are optional hints)
                break;
            default:
                $this->division_id = null;
                $this->district_id = null;
                $this->cluster_id  = null;
        }
    }
}

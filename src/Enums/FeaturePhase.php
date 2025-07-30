<?php

namespace Iambateman\Speedrun\Enums;

enum FeaturePhase: string
{
    case DISCOVERY = 'discovery';
    case DESCRIPTION = 'description';
    case PLANNING = 'planning';
    case EXECUTION = 'execution';
    case CLEANUP = 'cleanup';
    case COMPLETE = 'complete';

    public function canTransitionTo(FeaturePhase $phase): bool
    {
        return match ($this) {
            self::DISCOVERY => $phase === self::DESCRIPTION,
            self::DESCRIPTION => $phase === self::PLANNING,
            self::PLANNING => $phase === self::EXECUTION,
            self::EXECUTION => $phase === self::CLEANUP,
            self::CLEANUP => $phase === self::COMPLETE,
            self::COMPLETE => $phase === self::DESCRIPTION, // Allow improvement workflow
        };
    }

    public function getNextPhase(): ?FeaturePhase
    {
        return match ($this) {
            self::DISCOVERY => self::DESCRIPTION,
            self::DESCRIPTION => self::PLANNING,
            self::PLANNING => self::EXECUTION,
            self::EXECUTION => self::CLEANUP,
            self::CLEANUP => self::COMPLETE,
            self::COMPLETE => null,
        };
    }

    public function getPreviousPhase(): ?FeaturePhase
    {
        return match ($this) {
            self::DISCOVERY => null,
            self::DESCRIPTION => self::DISCOVERY,
            self::PLANNING => self::DESCRIPTION,
            self::EXECUTION => self::PLANNING,
            self::CLEANUP => self::EXECUTION,
            self::COMPLETE => self::CLEANUP,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DISCOVERY => 'Feature Discovery',
            self::DESCRIPTION => 'Feature Description',
            self::PLANNING => 'Implementation Planning',
            self::EXECUTION => 'Code Execution',
            self::CLEANUP => 'Artifact Cleanup',
            self::COMPLETE => 'Feature Complete',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DISCOVERY => '🔍',
            self::DESCRIPTION => '📝',
            self::PLANNING => '📋',
            self::EXECUTION => '⚡',
            self::CLEANUP => '🧹',
            self::COMPLETE => '✅',
        };
    }
}
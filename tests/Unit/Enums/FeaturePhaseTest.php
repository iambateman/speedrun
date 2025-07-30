<?php

use Iambateman\Speedrun\Enums\FeaturePhase;

describe('FeaturePhase', function () {

    it('has all expected phases', function () {
        $expectedPhases = [
            'discovery',
            'description', 
            'planning',
            'execution',
            'cleanup',
            'complete'
        ];
        
        $actualPhases = array_map(fn($case) => $case->value, FeaturePhase::cases());
        
        expect($actualPhases)->toBe($expectedPhases);
    });

    it('validates phase transitions correctly', function () {
        // Valid transitions
        expect(FeaturePhase::DISCOVERY->canTransitionTo(FeaturePhase::DESCRIPTION))->toBeTrue();
        expect(FeaturePhase::DESCRIPTION->canTransitionTo(FeaturePhase::PLANNING))->toBeTrue();
        expect(FeaturePhase::PLANNING->canTransitionTo(FeaturePhase::EXECUTION))->toBeTrue();
        expect(FeaturePhase::EXECUTION->canTransitionTo(FeaturePhase::CLEANUP))->toBeTrue();
        expect(FeaturePhase::CLEANUP->canTransitionTo(FeaturePhase::COMPLETE))->toBeTrue();
        
        // Invalid transitions
        expect(FeaturePhase::DISCOVERY->canTransitionTo(FeaturePhase::PLANNING))->toBeFalse();
        expect(FeaturePhase::DESCRIPTION->canTransitionTo(FeaturePhase::EXECUTION))->toBeFalse();
        expect(FeaturePhase::PLANNING->canTransitionTo(FeaturePhase::COMPLETE))->toBeFalse();
        expect(FeaturePhase::COMPLETE->canTransitionTo(FeaturePhase::DESCRIPTION))->toBeFalse();
    });

    it('returns correct next phase', function () {
        expect(FeaturePhase::DISCOVERY->getNextPhase())->toBe(FeaturePhase::DESCRIPTION);
        expect(FeaturePhase::DESCRIPTION->getNextPhase())->toBe(FeaturePhase::PLANNING);
        expect(FeaturePhase::PLANNING->getNextPhase())->toBe(FeaturePhase::EXECUTION);
        expect(FeaturePhase::EXECUTION->getNextPhase())->toBe(FeaturePhase::CLEANUP);
        expect(FeaturePhase::CLEANUP->getNextPhase())->toBe(FeaturePhase::COMPLETE);
        expect(FeaturePhase::COMPLETE->getNextPhase())->toBeNull();
    });

    it('returns correct previous phase', function () {
        expect(FeaturePhase::DISCOVERY->getPreviousPhase())->toBeNull();
        expect(FeaturePhase::DESCRIPTION->getPreviousPhase())->toBe(FeaturePhase::DISCOVERY);
        expect(FeaturePhase::PLANNING->getPreviousPhase())->toBe(FeaturePhase::DESCRIPTION);
        expect(FeaturePhase::EXECUTION->getPreviousPhase())->toBe(FeaturePhase::PLANNING);
        expect(FeaturePhase::CLEANUP->getPreviousPhase())->toBe(FeaturePhase::EXECUTION);
        expect(FeaturePhase::COMPLETE->getPreviousPhase())->toBe(FeaturePhase::CLEANUP);
    });

    it('provides human-readable labels', function () {
        expect(FeaturePhase::DISCOVERY->getLabel())->toBe('Feature Discovery');
        expect(FeaturePhase::DESCRIPTION->getLabel())->toBe('Feature Description');
        expect(FeaturePhase::PLANNING->getLabel())->toBe('Implementation Planning');
        expect(FeaturePhase::EXECUTION->getLabel())->toBe('Code Execution');
        expect(FeaturePhase::CLEANUP->getLabel())->toBe('Artifact Cleanup');
        expect(FeaturePhase::COMPLETE->getLabel())->toBe('Feature Complete');
    });

    it('provides appropriate icons', function () {
        expect(FeaturePhase::DISCOVERY->getIcon())->toBe('🔍');
        expect(FeaturePhase::DESCRIPTION->getIcon())->toBe('📝');
        expect(FeaturePhase::PLANNING->getIcon())->toBe('📋');
        expect(FeaturePhase::EXECUTION->getIcon())->toBe('⚡');
        expect(FeaturePhase::CLEANUP->getIcon())->toBe('🧹');
        expect(FeaturePhase::COMPLETE->getIcon())->toBe('✅');
    });

    it('can be created from string values', function () {
        expect(FeaturePhase::from('discovery'))->toBe(FeaturePhase::DISCOVERY);
        expect(FeaturePhase::from('planning'))->toBe(FeaturePhase::PLANNING);
        expect(FeaturePhase::from('complete'))->toBe(FeaturePhase::COMPLETE);
    });

    it('throws exception for invalid phase values', function () {
        expect(fn () => FeaturePhase::from('invalid-phase'))
            ->toThrow(ValueError::class);
    });

    it('supports tryFrom for safe conversion', function () {
        expect(FeaturePhase::tryFrom('description'))->toBe(FeaturePhase::DESCRIPTION);
        expect(FeaturePhase::tryFrom('invalid-phase'))->toBeNull();
    });

    it('maintains phase order for workflow', function () {
        $phases = FeaturePhase::cases();
        
        // Each phase should only allow transition to the next phase
        for ($i = 0; $i < count($phases) - 1; $i++) {
            $currentPhase = $phases[$i];
            $nextPhase = $phases[$i + 1];
            
            expect($currentPhase->canTransitionTo($nextPhase))
                ->toBeTrue("$currentPhase->value should transition to $nextPhase->value");
        }
    });

    it('prevents backward transitions', function () {
        $phases = FeaturePhase::cases();
        
        // Test that no phase can transition backward
        for ($i = 1; $i < count($phases); $i++) {
            $currentPhase = $phases[$i];
            $previousPhase = $phases[$i - 1];
            
            expect($currentPhase->canTransitionTo($previousPhase))
                ->toBeFalse("$currentPhase->value should not transition backward to $previousPhase->value");
        }
    });

    it('prevents skipping phases', function () {
        // Test that phases cannot skip ahead
        expect(FeaturePhase::DISCOVERY->canTransitionTo(FeaturePhase::PLANNING))->toBeFalse();
        expect(FeaturePhase::DESCRIPTION->canTransitionTo(FeaturePhase::EXECUTION))->toBeFalse();
        expect(FeaturePhase::PLANNING->canTransitionTo(FeaturePhase::CLEANUP))->toBeFalse();
        expect(FeaturePhase::EXECUTION->canTransitionTo(FeaturePhase::COMPLETE))->toBeFalse();
    });

});
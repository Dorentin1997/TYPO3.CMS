<?php
declare(strict_types=1);
namespace TYPO3\CMS\Extbase\Tests\Unit\DomainObject;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case
 */
class AbstractEntityTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithSimpleProperties()
    {
        $domainObject = new class extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {
            public $foo = 'Test';
            public $bar = 'It is raining outside';
        };
        $domainObject->_memorizeCleanState();

        $this->assertFalse($domainObject->_isDirty());
    }

    /**
     * @test
     */
    public function objectIsDirtyAfterCallingMemorizeCleanStateWithSimplePropertiesAndModifyingThePropertiesAfterwards()
    {
        $domainObject = new class extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {
            public $foo = 'Test';
            public $bar = 'It is raining outside';
        };
        $domainObject->_memorizeCleanState();
        $domainObject->bar = 'Now it is sunny.';

        $this->assertTrue($domainObject->_isDirty());
    }

    /**
     * @test
     */
    public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithObjectProperties()
    {
        $domainObject = new class extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {
            public $foo;
            public $bar = 'It is raining outside';
        };
        $domainObject->foo = new \DateTime();
        $domainObject->_memorizeCleanState();

        $this->assertFalse($domainObject->_isDirty());
    }

    /**
     * @test
     */
    public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithOtherDomainObjectsAsProperties()
    {
        $domainObject = new class extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {
            public $foo;
            public $bar;
        };

        $secondDomainObject = new class extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {
            public $foo;
            public $bar;
        };

        $secondDomainObject->_memorizeCleanState();
        $domainObject->foo = $secondDomainObject;
        $domainObject->bar = 'It is raining outside';
        $domainObject->_memorizeCleanState();

        $this->assertFalse($domainObject->_isDirty());
    }
}

<?php
namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testTheAutomaticApiTokenSettingWhenUserIsCreated(): void
    {
        $user = new User();
        $this->assertNotNull($user->getApiToken());
    }

    public function testThanAnUserHasAtLeastOneRoleUser(): void
    {
        $user = new User();
        $this->assertContains('ROLE_USER', $user->getRoles());
    }
}

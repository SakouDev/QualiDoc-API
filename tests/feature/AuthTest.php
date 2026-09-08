<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class AuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private $testDb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb = db_connect();
        $this->testDb->transStart();
    }

    protected function tearDown(): void
    {
        $this->testDb->transRollback();
        parent::tearDown();
    }

    public function testRegisterCreatesAccountAndReturnsToken(): void
    {
        $result = $this->post('/api/auth/register', [
            'nom' => 'Test',
            'prenom' => 'Unit',
            'email' => 'unit.' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $result->assertStatus(201);
        $this->assertFalse(json_decode($result->getJSON(), true)['patient']['admin']);
    }

    public function testRegisterRejectsShortPassword(): void
    {
        $result = $this->post('/api/auth/register', [
            'nom' => 'Test',
            'prenom' => 'Unit',
            'email' => 'unit.' . uniqid() . '@example.com',
            'password' => '123',
        ]);

        $result->assertStatus(400);
    }

    public function testRegisterIgnoresMassAssignedAdminFlag(): void
    {
        $result = $this->post('/api/auth/register', [
            'nom' => 'Hack',
            'prenom' => 'Er',
            'email' => 'unit.' . uniqid() . '@example.com',
            'password' => 'password123',
            'admin' => true,
        ]);

        $this->assertFalse(json_decode($result->getJSON(), true)['patient']['admin']);
    }

    public function testLoginWithWrongPasswordFails(): void
    {
        $result = $this->post('/api/auth/login', [
            'email' => 'luc.dupont@example.com',
            'password' => 'mot-de-passe-invalide',
        ]);

        $result->assertStatus(401);
    }

    public function testMeRequiresToken(): void
    {
        $result = $this->get('/api/me');

        $result->assertStatus(401);
    }
}

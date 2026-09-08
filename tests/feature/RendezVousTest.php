<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class RendezVousTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private $testDb;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb = db_connect();
        $this->testDb->transStart();

        $login = $this->post('/api/auth/login', [
            'email' => 'luc.dupont@example.com',
            'password' => 'password',
        ]);

        $this->token = json_decode($login->getJSON(), true)['token'];
    }

    protected function tearDown(): void
    {
        $this->testDb->transRollback();
        parent::tearDown();
    }

    public function testBookingRejectsPastDate(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->post('/api/rendez-vous', [
                'medecin_id' => 1,
                'date_heure' => '2020-01-01 10:00:00',
            ]);

        $result->assertStatus(400);
    }

    public function testBookingSucceedsThenPreventsDoubleBooking(): void
    {
        $payload = ['medecin_id' => 1, 'date_heure' => '2027-01-15 10:00:00'];
        $headers = ['Authorization' => 'Bearer ' . $this->token];

        $first = $this->withHeaders($headers)->post('/api/rendez-vous', $payload);
        $first->assertStatus(201);

        $second = $this->withHeaders($headers)->post('/api/rendez-vous', $payload);
        $second->assertStatus(409);
    }

    public function testCreatingRendezVousRequiresAuth(): void
    {
        $result = $this->post('/api/rendez-vous', [
            'medecin_id' => 1,
            'date_heure' => '2027-01-15 10:00:00',
        ]);

        $result->assertStatus(401);
    }
}

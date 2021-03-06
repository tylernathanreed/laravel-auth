<?php

use Mockery as m;

class AuthDatabaseUserProviderTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRetrieveByIDReturnsUserWhenUserIsFound()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn(['id' => 1, 'name' => 'Dayle']);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new Reed\Auth\DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveById(1);

        $this->assertInstanceOf('Reed\Auth\GenericUser', $user);
        $this->assertEquals(1, $user->getAuthIdentifier());
        $this->assertEquals('Dayle', $user->name);
    }

    public function testRetrieveByIDReturnsNullWhenUserIsNotFound()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn(null);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new Reed\Auth\DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveById(1);

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsReturnsUserWhenUserIsFound()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('where')->once()->with('username', 'dayle');
        $conn->shouldReceive('first')->once()->andReturn(['id' => 1, 'name' => 'taylor']);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new Reed\Auth\DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials(['username' => 'dayle', 'password' => 'foo']);

        $this->assertInstanceOf('Reed\Auth\GenericUser', $user);
        $this->assertEquals(1, $user->getAuthIdentifier());
        $this->assertEquals('taylor', $user->name);
    }

    public function testRetrieveByCredentialsReturnsNullWhenUserIsFound()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('where')->once()->with('username', 'dayle');
        $conn->shouldReceive('first')->once()->andReturn(null);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new Reed\Auth\DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials(['username' => 'dayle']);

        $this->assertNull($user);
    }

    public function testCredentialValidation()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $hasher->shouldReceive('check')->once()->with('plain', 'hash')->andReturn(true);
        $provider = new Reed\Auth\DatabaseUserProvider($conn, $hasher, 'foo');
        $user = m::mock('Reed\Auth\Contracts\Authenticatable');
        $user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }
}
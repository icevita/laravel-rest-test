<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Everyman\Neo4j\Cypher;
use Auth;

class TecheryTest extends TestCase
{
    //use WithoutMiddleware;

    public function testClean()
    {
        $this->testDeleteUser();
    }

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        $this->visit('/')
             ->see('Laravel 5')
            ->dontSee('Rails');
    }

    /**
     * Test user creating.
     *
     * @return void
     */
    public function testCreateUser()
    {
        $response = $this->post('/api/v1/users', ['name' => 'test', 'email' => 'test@test.test',
                                      'password' => 'password','password_confirmation'=>'password'])
            ->seeJson([
                'msg' => 'success',
            ]);
    }



    /**
     * Test basic auth
     */
    public function testBasicAuth()
    {
        $this->call('GET', '/api/v1/users', [], [], [], ['username' => 'test@test.test', 'password' => '3232']);
        $this->assertResponseStatus(401);
    }

    /**
     * Create second user for request test
     */
    public function test2CreateUser()
    {
        $response = $this->post('/api/v1/users', ['name' => 'test', 'email' => 'test2@test.test',
                                                  'password' => 'password','password_confirmation'=>'password'])
            ->seeJson([
                'msg' => 'success',
            ]);
    }


    /**
     * Test add friend request.
     *
     * @return void
     */
    public function testAddFriendRequest()
    {

        $client = DB::connection('neo4j')->getClient();

        $queryTemplate = "MATCH (n:users) WHERE n.email='test@test.test' or n.email='test2@test.test' return n";
        $query = new Cypher\Query($client, $queryTemplate);
        $result = $query->getResultSet();

        $friends = [];
        foreach($result as $row) {
            $friends[] = $row['friend']->getId();
        }

        Auth::loginUsingId($friends[0]);
        $response = $this->post('/api/v1/users/friends/'.$friends[1])
            ->seeJson([
                'msg' => 'success',
            ]);
    }


    //TODO: add other tests

    /**
     * Test user delete.
     *
     * @return void
     */
    public function testDeleteUser()
    {
        $client = DB::connection('neo4j')->getClient();


        $queryTemplate = "MATCH (n:users) WHERE n.email='test@test.test' or n.email='test2@test.test'
        OPTIONAL MATCH (n:users), (n:users)-[r]-b
        WHERE n.email='test@test.test' or n.email='test2@test.test' DELETE n,r";

        $query = new Cypher\Query($client, $queryTemplate);
        $query->getResultSet();
    }
}

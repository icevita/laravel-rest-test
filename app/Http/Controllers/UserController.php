<?php

namespace App\Http\Controllers;

use Everyman\Neo4j\Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;

use Illuminate\Support\Facades\DB;
use Auth;
use Everyman\Neo4j\Cypher;


class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {

            $response = [
                'users' => []
            ];
            $statusCode = 200;
            $users = User::paginate(10);

            foreach ($users as $user) {

                $response['users'][] = [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email
                ];


            }


        } catch (Exception $e) {
            $statusCode = 404;
        } finally {
            return response()->json($response, $statusCode);
        }
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'     => 'max:255',
            'email'    => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = \Hash::make($request->password);
        if ($user->save()) {
            return response()->json([
                'msg' => 'success'
            ], 201);
        } else {
            return response()->json([
                'msg'   => 'error',
                'error' => 'cannot create user'
            ], 400);
        }
    }

    /**
     * Add user to friends request
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function addRequest($id)
    {
        $user = Auth::user();

        $client = DB::connection('neo4j')->getClient();

        $queryTemplate = "MATCH (user:users), (friend:users)
                          WHERE NOT user-[:FRIENDS]-friend AND id(user)={user_id} and id(friend)={friend_id}
                          MERGE (user)-[fr:FRIEND_REQUEST]->(friend) RETURN fr";

        $query = new Cypher\Query($client, $queryTemplate, ['user_id' => $user->id, 'friend_id' => intval($id)]);
        $result = $query->getResultSet();

        if (count($result)) {
            return response()->json([
                'msg' => 'success'
            ], 201);
        } else {
            return response()->json([
                'msg'   => 'error',
                'error' => 'cannot create friend request'
            ], 400);
        }

    }

    /**
     * Add user to friends request
     *
     * @return \Illuminate\Http\Response
     */
    public function getRequests()
    {
        $user = Auth::user();

        $client = DB::connection('neo4j')->getClient();

        $queryTemplate = "MATCH (user:users)-[fr:FRIEND_REQUEST]->(friend:users)
                          WHERE id(user) = {user_id}
                          RETURN friend";

        $query = new Cypher\Query($client, $queryTemplate, ['user_id' => $user->id]);
        $result = $query->getResultSet();

        $friends = [];
        foreach($result as $row) {
            $user_fields = $row['friend']->getProperties();
            $user_fields['id'] = $row['friend']->getId();
            $friends[] = $user_fields;
        }

        return response()->json([
            'msg' => 'success',
            'friends' => $friends,
            'count' => count($friends)
        ], 200);
    }

    /**
     * Add user to friends
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function acceptRequest($id)
    {
        $user = Auth::user();

        $client = DB::connection('neo4j')->getClient();

        $queryTemplate = "MATCH (user:users)-[fr:FRIEND_REQUEST]->(friend:users)
                          WHERE id(user)={user_id} and id(friend)={friend_id}
                          MERGE (user)-[f:FRIENDS]-(friend)
                          DELETE fr RETURN f";

        $query = new Cypher\Query($client, $queryTemplate, ['user_id' => $user->id, 'friend_id' => intval($id)]);
        $result = $query->getResultSet();

        if (count($result)) {
            return response()->json([
                'msg' => 'success'
            ], 204);
        } else {
            return response()->json([
                'msg'   => 'error',
                'error' => 'cannot accept friend request'
            ], 400);
        }
    }

    /**
     * Add user to friends
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function declineRequest($id)
    {
        $user = Auth::user();

        $client = DB::connection('neo4j')->getClient();

        $queryTemplate = "MATCH (user:users)-[fr:FRIEND_REQUEST]->(friend:users)
                          WHERE id(user)={user_id} and id(friend)={friend_id}
                          DELETE fr";

        try {
            $query = new Cypher\Query($client, $queryTemplate, ['user_id' => $user->id, 'friend_id' => intval($id)]);
            $query->getResultSet();
        } catch(Exception $e){
            return response()->json([
                'msg'   => 'error',
                'error' => 'cannot decline friend request',
                'error' => $e->getMessage()
            ], 400);
        }

        return response()->json([
            'msg' => 'success'
        ], 200);
    }

    /**
     * Display a listing of user's friends.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getFriends(Request $request)
    {
        $this->validate($request, [
            'nested'    => 'min:1|max:5',
        ]);

        $nested = $request->input('nested', 1);


        $user = Auth::user();

        $client = DB::connection('neo4j')->getClient();

        $queryTemplate = "match (u:users)-[r:FRIENDS*0..".$nested."]-(f:users)
                            WHERE id(u)={user_id} and id(f)<>{user_id} return distinct f";

        $query = new Cypher\Query($client, $queryTemplate, ['user_id' => $user->id, 'nested' => $nested]);
        $result = $query->getResultSet();

        $friends = [];
        foreach($result as $row) {
            $user_fields = $row['friends']->getProperties();
            $user_fields['id'] = $row['friends']->getId();
            $friends[] = $user_fields;
        }

        return response()->json([
            'msg' => 'success',
            'friends' => $friends,
            'count' => count($friends)
        ], 200);
    }
}

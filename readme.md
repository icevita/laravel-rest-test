`composer install` to install dependencies
`vendor/bin/phpunit` to run tests

Use basic auth to make requests.

Accept: application/json
Content-type: application/json


1) To create user:
```
POST /api/v1/users
{
    "name":"bla bla",
    "email":"email1@bla.bla",
    "password":"blabla",
    "password_confirmation":"blabla"
}
```
email is unique


2) To get all users(with pagination):
```
GET /api/v1/users
```

3) Add user to friends request:
```
POST /api/v1/users/friends/{user_id}

```

4) Get user friends requests:
```
GET /api/v1/users/friends/requests
```

5) Accept/Decline user's friend request:
```
PUT /api/v1/users/friends/{id}/accept
PUT /api/v1/users/friends/{id}/decline
```

6) Get user friends:
```
GET /api/v1/users/friends?nesting=1

default nesting = 1
```

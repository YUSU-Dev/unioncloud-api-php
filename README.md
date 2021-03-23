# UnionCloud API Wrapper (PHP)
By [Liam McDaid, University of York Students' Union (YUSU)](http://www.yusu.org)

A wrapper to allow integration with the APIs of the [UnionCloud](http://www.unioncloud.co.uk) platform operated by NUS.

**WARNING:** The apis are at Union level and should only be used by the Union. Access to the apis should not be given to any student groups under any circumstances.

## Requirements
* PHP5.5+
* [Composer](https://getcomposer.org/)

## Installation
Add ``yusu/unioncloud-api-php`` as a require dependency in your ``composer.json`` file:

```bash
composer require yusu/unioncloud-api-php
```

## Usage
Create an instance of the api wrapper and tell it which site to use

```php
use UnionCloud\Api;
$api = new Api();
$api->setHost("www.yusu.org");
```

You can inject debug info about the request into the response by passing ``"include_debug_info" => true`` in either the constructor

```php
$api = new Api(["include_debug_info" => true]);
```

or

```php
# UnionCloud API Wrapper (PHP)

By [Liam McDaid, University of York Students' Union (YUSU)](http://www.yusu.org)



A wrapper to allow integration with the APIs of the [UnionCloud](http://www.unioncloud.co.uk) platform operated by NUS.


    **This api wrapper is not maintained anymore.**

 A potential replacement is linked here from [Bristol SU](https://github.com/bristol-su/nus-unioncloud-api-wrapper).


## Old readme

        **WARNING:** The apis are at Union level and should only be used by the Union. Access to the apis should not be given to any student groups under any circumstances.
    
    ## Requirements
    * PHP5.5+
    * [Composer](https://getcomposer.org/)
    
    ## Installation
    Add ``yusu/unioncloud-api-php`` as a require dependency in your ``composer.json`` file:
    
    ```bash
    composer require yusu/unioncloud-api-php
    ```
    
    ## Usage
    Create an instance of the api wrapper and tell it which site to use
    
    ```php
    use UnionCloud\Api;
    $api = new Api();
    $api->setHost("www.yusu.org");
    ```
    
    You can inject debug info about the request into the response by passing ``"include_debug_info" => true`` in either the constructor
    
    ```php
    $api = new Api(["include_debug_info" => true]);
    ```
    
    or
    
    ```php
    $api->setOptions(["include_debug_info" => true]);
    ```
    
    ### Authenticating
    You need to authenticate before you are able to make any api calls
    
    ```php
    $api->authenticate($user_email, $user_password, $app_id, $app_password);
    ```
    
    ``$app_id`` and ``$app_password`` can be created via your unions dashboard ( **Admin** > **Setup** > **Developers** )
    
    ``$user_email`` and ``$user_password`` should be the login details of a valid user for your union that has the permissions to access the apis. **Note:** This should be a service account. Its best to contact NUS via Zendesk to get setup with these as there are some other settings which need to be enabled for the apis to work.
    
      
    ```php
    $api->setAuthToken($auth_token, $expiry_timestamp);
    ```
            
    ### Making API calls
    
    Once you have successfully authenticated you are able to make api calls using the wrapper. Please consult the documentation apiary and the methods which this wrapper exposes to make calls. A few examples are given below:
    
    ```php
    // users
    $users = $api->users();
    $user = $api->user_search(["id" => "lm638"]);
    
    // events
    $events = $api->events();
    $event = $api->event_get($event_id);
    $api->event_update($event_id, ["event_name" => "New Event Name"]);
    
    // groups
    $groups = $api->groups();
    ```
    
    ## Change Log
    
    ### [0.2.0] 12th October 2017
    - Fix issue ca-cert in macOS
    - Reduce dependency on php to 5
    
    ### [0.1.0] 25th March 2017
    - Corrected: version of a required dependency in composer.json
    
    ### [0.1.0] 25th March 2017
    - New Project created 
    - Added: Goutte to handle making the api calls
    - Added: All current apis
    - Added: Apis throw exceptions when errors are detected
    - Added: Option to show debug information about the request (including any pagination info)
    
$api->setOptions(["include_debug_info" => true]);
```

### Authenticating
You need to authenticate before you are able to make any api calls

```php
$api->authenticate($user_email, $user_password, $app_id, $app_password);
```

``$app_id`` and ``$app_password`` can be created via your unions dashboard ( **Admin** > **Setup** > **Developers** )

``$user_email`` and ``$user_password`` should be the login details of a valid user for your union that has the permissions to access the apis. **Note:** This should be a service account. Its best to contact NUS via Zendesk to get setup with these as there are some other settings which need to be enabled for the apis to work.

  
```php
$api->setAuthToken($auth_token, $expiry_timestamp);
```
        
### Making API calls

Once you have successfully authenticated you are able to make api calls using the wrapper. Please consult the documentation apiary and the methods which this wrapper exposes to make calls. A few examples are given below:

```php
// users
$users = $api->users();
$user = $api->user_search(["id" => "lm638"]);

// events
$events = $api->events();
$event = $api->event_get($event_id);
$api->event_update($event_id, ["event_name" => "New Event Name"]);

// groups
$groups = $api->groups();
```

## Change Log

### [0.2.0] 12th October 2017
- Fix issue ca-cert in macOS
- Reduce dependency on php to 5

### [0.1.0] 25th March 2017
- Corrected: version of a required dependency in composer.json

### [0.1.0] 25th March 2017
- New Project created 
- Added: Goutte to handle making the api calls
- Added: All current apis
- Added: Apis throw exceptions when errors are detected
- Added: Option to show debug information about the request (including any pagination info)

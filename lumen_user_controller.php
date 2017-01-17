<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\UserService;

/**
 * Class UserController
 *
 * @package App\Http\Controllers
 */
class UserController extends Controller
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * UserController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function register(Request $request)
    {
        $this->validate($request,
            [
                'first_name'   => 'required',
                'last_name'    => 'required',
                'email'        => 'required|email|unique:users',
                'mobile_phone' => 'phone:AUTO',
                'password'     => [
                    'required',
                    'min:8',
                    'regex:/^(?=.*?[a-z])(?=.*?[0-9])(?=.*?[A-Z]|.*?[#?!@$%^&*-]).{1,}$/',
                    'different:email'
                ],
                'company'      => 'required',
            ],
            [
                'mobile_phone.phone' => 'The mobile phone must start with a + sign and consist of numbers only.',
                'password.regex'     => 'The password must have at least 1 number, letter, special symbol or upper case latter.',
            ]
        );

        $user = $this->userService->createUser(
            $request->input('first_name'),
            $request->input('last_name'),
            $request->input('email'),
            $request->input('mobile_phone'),
            $request->input('password'),
            $request->input('company')
        );

        return new Response($user);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function getToken(Request $request)
    {
        $this->validate($request,
            [
                'email'    => 'required',
                'password' => 'required',
            ]
        );

        $access_token = $this->userService->getToken(
            $request->input('email'),
            $request->input('password')
        );

        return new Response(['access_token' => $access_token]);
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function show(Request $request)
    {
        return $request->user();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function update(Request $request)
    {
        $this->validate($request,
            [
                'first_name'   => 'sometimes|required',
                'last_name'    => 'sometimes|required',
                'email'        => 'sometimes|required|email|unique:users',
                'mobile_phone' => 'sometimes|phone:AUTO',
                'company'      => 'sometimes|required',
            ],
            [
                'required' => 'The ":attribute" field cannot be blank.',
                'mobile_phone.phone' => 'The mobile phone must start with a + sign and consist of numbers only.',
            ]
        );

        $user = $request->user();
        $user->update($request->except(['password']));

        return new Response($user);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function changePassword(Request $request)
    {
        $this->validate($request,
            [
                'new_password' => 'required',
                'old_password' => 'required',
            ]
        );

        $this->userService->changeUserPassword(
            $request->user(),
            $request->request->get('new_password'),
            $request->request->get('old_password')
        );

        return new Response('', 204);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function requestResetPassword(Request $request)
    {
        $this->validate($request,
            [
                'email' => 'required',
            ]
        );

        $this->userService->requestResetUserPassword($request->input('email'));

        return new Response('', 204);
    }

    /**
     * @param Request $request
     * @param $token
     *
     * @return Response
     */
    public function resetPassword(Request $request, $token)
    {
        $this->validate($request,
            [
                'password' => [
                    'required',
                    'min:8',
                    'not_equal_to_user_email:' . $token,
                    'regex:/^(?=.*?[a-z])(?=.*?[0-9])(?=.*?[A-Z]|.*?[#?!@$%^&*-]).{1,}$/',
                ],
                'repeated_password' => 'required|same:password'
            ],
            [
                'password.not_equal_to_user_email' => 'The password should not be equal to email.',
                'password.regex'                   => 'The password must have at least 1 number, letter, special symbol or upper case latter.',
            ]
        );

        $accessToken = $this->userService->resetUserPassword(
            $token,
            $request->input('password')
        );

        return new Response(['access_token' => $accessToken]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function sendConfirmationEmail(Request $request)
    {
        $user = $request->user();

        $this->userService->sendConfirmationEmail($user);

        return new Response('', 204);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function confirmEmail(Request $request)
    {
        $this->validate($request,
            [
                'confirmation_token' => 'required',
            ]
        );

        $user = $request->user();

        $accessToken = $this->userService->confirmUserEmail(
            $request->input('confirmation_token'),
            $user
        );

        return new Response(['access_token' => $accessToken]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function isPasswordResetTokenValid(Request $request)
    {
        $this->validate($request,
            [
                'password_reset_token' => 'required',
            ]
        );

        $isTokenValid = $this->userService->isPasswordResetTokenValid($request->request->get('password_reset_token'));

        return new Response(['is_valid' => $isTokenValid]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function delete(Request $request)
    {
        $request->user()->delete();

        return new Response('', 204);
    }
}

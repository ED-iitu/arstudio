<?php
namespace App\Http\Controllers\API\V1;
use App\Http\Controllers\Controller;
use App\Models\Ar;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function redirectToProvider(string $driver): \Symfony\Component\HttpFoundation\RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        return Socialite::driver($driver)->redirect();
    }

    public function handleProviderCallback(string $driver)
    {
        try {
            $driverUser = Socialite::driver($driver)->user();
            $user       = User::where('email', $driverUser->email)->first();

            if (!$user) {
                $user           = new User();
                $user->name     = $driverUser->name;
                $user->email    = $driverUser->email;
                $user->password = bcrypt(Str::random(16));
                $user->provider = $driver;
                $user->save();
            }

            $token   = $user->createToken('API Token')->plainTextToken;
            $cookies = Cookie::make('token', $token, 84000, null, 'https://pro.arstudio.kz', false, false);

            return redirect()->to('https://pro.arstudio.kz?token=' . $token)->withCookie($cookies);
        } catch (\Exception $e) {
            abort(403);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user  = Auth::user();
            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json(['token' => $token], 200);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    public function register(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $phone       = $request->get('phone');
        $user        = User::where('email', $credentials['email'])->first();

        if ($user) {
            return response()->json(
                [
                    'status'  => 'error',
                    'message' => 'Пользователь уже существует'
                ], 400
            );
        }

        $user           = new User();
        $user->name     = $credentials['email'];
        $user->email    = $credentials['email'];
        $user->password = bcrypt($credentials['password']);

        if ($phone) {
            $user->phone = $phone;
        }
        $user->save();

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    public function getUser()
    {
        if (Auth::user() === null) {
            return response()->json(
                [
                    'status'  => 'error',
                    'message' => 'Пользователь не найден'
                ], 404
            );
        }

        Auth::user()->ar_count = Ar::where('user_id', Auth::user()->id)->count();

        return response()->json(
            [
                'status'  => 'ok',
                'data' => Auth::user()->load(['tariff', 'transactions'])
            ], 200
        );
    }

    public function updateProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $profile     = Auth::user();
        $password    = $request->get('password');
        $oldPassword = $request->get('old_password');

        if (isset($password)) {
            if (Auth::user()->provider !== null) {
                if (!Hash::check($oldPassword, Auth::user()->password)) {
                    return response()->json(
                        [
                            'status'  => 'error',
                            'message' => 'Текущий пароль введен не верно'
                        ], 400
                    );
                }
            }

            $password = bcrypt($password);
            $data     = [
                'name'     => $request->get('name'),
                'email'    => $request->get('email'),
                'phone'    => $request->get('phone'),
                'password' => $password
            ];

            $profile->update($data);

            return response()->json(
                [
                    'status'  => 'ok',
                    'message' => 'Данные успешно обновлены'
                ], 200
            );
        }

        $profile->update($request->all());

        return response()->json(
            [
                'status'  => 'ok',
                'message' => 'Данные успешно обновлены'
            ], 200
        );
    }
}

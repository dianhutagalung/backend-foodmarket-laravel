<?php

namespace App\Http\Controllers\API;

use App\Actions\Fortify\PasswordValidationRules;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use PasswordValidationRules;

    public function login(Request $request)
    {
        try {
            
            $rules = [
                'email' => 'email|required',
                'password' => 'required'
            ];

            // validasi input
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()){
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ],'Authentication Failed', 500);
            }

            // mengecek credentials (login)
            $credentials = request(['email','password']);
            if(!Auth::attempt($credentials)){
                return ResponseFormatter::error([
                    'message' => 'User dan password tidak cocok!'
                ],'Authentication Failed', 500);
            }

            // jika hash tidak sesuai maka beri error
            $user = User::where('email', $request->email)->first();
            if(!hash::check($request->password, $user->password, [])){
                throw new \Exception('Invalid Credentials');
            }

            // jika berhasil maka loginkan
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type'=> 'Bearer',
                'user' => $user
            ], 'Authenticated');

        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }

    }

    public function register(Request $request)
    {
        try {
            $rules = [
                'name' => ['required','string','max:255'],
                'email' => ['required','string','email','max:255','unique:users'],
                'password' => $this->passwordRules()
            ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()){
                return ResponseFormatter::error([
                    'message' => 'Validation error',
                    'error' => $validator->errors()
                ], 'Validation Failed', 400);
            }

            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'houseNumber' => $request->houseNumber,
                'phoneNumber' => $request->phoneNumber,
                'city' => $request->city,
                'password' => Hash::make($request->password)
            ]);

            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ]);

        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error,
            ],'Authentication Failed', 500);
        }
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();

        return ResponseFormatter::success($token, 'Token Revoke');
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data profile user berhasil diambil');
    }

    public function updateProfile(Request $request)
    {
        $data = $request->all();

        $user = Auth::user();

        $validator = Validator::make($data, [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ]);

        if($validator->fails())
        {
            return ResponseFormatter::error(
                ['error' => $validator->errors(),
                'message' => 'Email sudah tersedia'
                ],'Update Email fails', 401
            );
        }


        $user->update($data);

        return ResponseFormatter::success($user, 'Profile Update');
    }

    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:2048'
        ]);

        if($validator->fails())
        {
            return ResponseFormatter::error(
                ['error' => $validator->errors()],
                'Update Photo fails', 401
            );
        }

        if($request->file('file'))
        {   
            // upload foto
            $file = $request->file->store('assets/user','public');

            // simpan foto ke database (urlnya)
            $user = Auth::user();
            $user->profile_photo_path = $file;
            $user->update();

            return ResponseFormatter::success([$file], 'File successfully uploaded');
        }
    }
}

<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

class PaymentApiController extends Controller
{
    protected ?string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('paystack.secret_key');
        if (empty($this->secretKey)) {
            Log::critical('PAYSTACK_SECRET_KEY is missing or not loaded from config/paystack.php');
        }
    }

    public function initialize(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        $courseId = $request->input('course_id');
        $course = Course::findOrFail($courseId);

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->courses()->where('course_id', $course->id)->exists()) {
            return response()->json(['error' => 'You are already enrolled in this course.'], 400);
        }

        try {
            if (empty($this->secretKey)) {
                throw new Exception("Payment API Key is not configured.");
            }

            $initializeUrl = config('paystack.payment_url') . '/transaction/initialize';
            //$amountInKobo = ($course->currentPrice->amount ?? 99.00) * 100;


            $amount = $course->currentPrice->amount ?? 99;
            $amountInKobo = (int)($amount * 100);

            $response = Http::withToken($this->secretKey)
                ->post($initializeUrl, [
                    'email' => $user->email,
                    'amount' => $amountInKobo,
                    'callback_url' => config('paystack.callback_url'),
                    'metadata' => [
                        'course_id' => $course->id,
                        'user_id' => $user->id,
                        'custom_fields' => [
                            'item_name' => $course->title,
                            'item_id' => $course->id,
                        ]
                    ]
                ]);

            $body = $response->json();

            if (!$response->successful() || empty($body['data']['authorization_url'])) {
                Log::error('Paystack Initialization API Failure', ['response' => $body]);
                throw new Exception($body['message'] ?? 'Paystack initialization failed.');
            }

            return response()->json([
                'authorization_url' => $body['data']['authorization_url'],
            ]);
        } catch (Exception $e) {
            Log::error("Paystack Initialization Error: " . $e->getMessage());
            return response()->json(['error' => 'Payment initialization failed. Please try again.'], 500);
        }
    }

    public function callback(Request $request)
{
    $reference = $request->query('reference');
    if (!$reference) {
        return redirect(config('frontend.base_url') . '/category?error=Payment reference not provided.');
    }

    try {
        if (empty($this->secretKey)) {
            throw new Exception("Payment API Key is not configured.");
        }

        $verifyUrl = config('paystack.payment_url') . "/transaction/verify/{$reference}";
        $response = Http::withToken($this->secretKey)
            ->get($verifyUrl);
        $body = $response->json();
        Log::info('Paystack Verification Response:', $body ?? []);

        if (!$response->successful() || empty($body['data']) || $body['status'] !== true) {
            throw new Exception($body['message'] ?? 'Transaction verification failed.');
        }

        $data = $body['data'];

        if ($data['status'] !== 'success') {
            $message = $data['gateway_response'] ?? 'Payment was not successful.';
            return redirect(config('frontend.base_url') . '/category?error=' . urlencode($message));
        }

        $courseId = $data['metadata']['course_id'] ?? null;
        $userId = $data['metadata']['user_id'] ?? null;
        if (!$courseId || !$userId) {
            throw new Exception('Payment verified, but metadata missing. Ref: ' . $reference);
        }

        DB::beginTransaction();
        $user = User::findOrFail($userId);
        $course = Course::findOrFail($courseId);

        if (!$user->courses()->where('course_id', $course->id)->exists()) {
            $user->courses()->attach($courseId, [
                'payment_reference' => $reference,
                'paid_amount' => $data['amount'] / 100,
                'paid_at' => now(),
            ]);
        }

        DB::commit();

        $successMessage = 'Payment successful and enrollment complete!';

        if ($course->type === 'online') {
             Log::error("Paystack yes " );
            return redirect(config('frontend.base_url') . "/course/{$course->slug}/watch?success={$successMessage}");
        } else {
             Log::error("Paystack vooedj " );
            return redirect(config('frontend.base_url') . "/courses/{$course->slug}?success={$successMessage}");
        }
    } catch (Exception $e) {
        DB::rollBack();
        Log::error("Paystack Callback Error: " . $e->getMessage(), ['reference' => $reference]);
        return redirect(config('frontend.base_url') . '/category?error=' . urlencode('Payment verification failed: ' . $e->getMessage()));
    }
}
}
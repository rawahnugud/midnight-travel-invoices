<?php

namespace App\Http\Controllers;

use App\Models\BankLetter;
use App\Support\TransferAmount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BankLetterController extends Controller
{
    public function index()
    {
        return view('transfers.index', ['letters' => BankLetter::latest('updated_at')->orderByDesc('id')->paginate(20)]);
    }

    public function create()
    {
        return view('transfers.form', ['letter' => new BankLetter(), 'data' => []]);
    }

    public function edit(BankLetter $letter)
    {
        return view('transfers.form', ['letter' => $letter, 'data' => $letter->details]);
    }

    public function copy(BankLetter $letter)
    {
        return view('transfers.form', ['letter' => new BankLetter(), 'data' => array_merge($letter->details, ['date' => today()->toDateString()])]);
    }

    private function rules(bool $complete): array
    {
        $presence = $complete ? 'required' : 'nullable';
        return [
            'language' => 'required|in:en,ar',
            'date' => 'required|date_format:Y-m-d',
            'bank' => "$presence|string|max:150",
            'holder' => "$presence|string|max:160",
            'account' => "$presence|regex:/^[0-9]{1,34}$/D",
            'signer' => "$presence|string|max:160",
            'role' => "$presence|string|max:120",
            'rows' => 'required|array|min:1|max:50',
            'rows.*' => 'required|array:name,account,amount',
            'rows.*.name' => "$presence|string|max:160",
            'rows.*.account' => "$presence|regex:/^[0-9]{1,34}$/D",
            'rows.*.amount' => [$presence, 'regex:/^[0-9]{1,10}(?:[.][0-9]{1,2})?$/D', 'numeric', 'gt:0'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request->input('action') === 'print'));
        $letter = BankLetter::create(['details' => $data]);
        return $this->saved($request, $letter);
    }

    public function update(Request $request, BankLetter $letter)
    {
        $revision = $request->validate(['revision' => 'required|integer|min:1'])['revision'];
        $data = $request->validate($this->rules($request->input('action') === 'print'));
        $updated = BankLetter::whereKey($letter->id)->where('revision', $revision)
            ->update(['details' => json_encode($data, JSON_THROW_ON_ERROR), 'revision' => $revision + 1]);
        abort_unless($updated, 409, __('letters.conflict'));
        return $this->saved($request, $letter);
    }

    private function saved(Request $request, BankLetter $letter)
    {
        return redirect()->route($request->input('action') === 'print' ? 'letters.print' : 'letters.edit', $letter)
            ->with('success', __('letters.saved'));
    }

    public function print(BankLetter $letter)
    {
        $data = $letter->details;
        $validator = Validator::make($data, $this->rules(true));
        if ($validator->fails()) {
            return redirect()->route('letters.edit', $letter)->withErrors($validator);
        }
        $total = 0;
        foreach ($data['rows'] as &$row) {
            $row['minor'] = TransferAmount::minor($row['amount']);
            $total += $row['minor'];
        }
        unset($row);
        app()->setLocale($data['language']);
        return response()->view('transfers.print', compact('letter', 'data', 'total'))->header('Cache-Control', 'private, no-store');
    }
}

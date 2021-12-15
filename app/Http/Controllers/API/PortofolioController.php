<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortofolioResource;
use App\Models\Mahasiswa;
use App\Models\Portofolio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class PortofolioController extends Controller
{
    public function index()
    {
        $mhs_id = User::findOrFail(Auth::user()->id)->mahasiswa->id;
        return PortofolioResource::collection(
            Mahasiswa::find($mhs_id)->portofolio()->paginate(5)
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kategori_kegiatan_id' => ['required'],
            'jenis_kegiatan_id' => ['required'],
            'nama_kegiatan' => ['required', 'string', 'max:191'],
            'penyelenggara' => ['required', 'string', 'max:191'],
            'tahun' => ['required', 'digits_between:4,4', 'numeric'],
            'bukti' => ['required', 'file', 'mimes:jpg,png,jpeg,pdf', 'max:2048']
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $portofolio = Portofolio::create([
                'mahasiswa_id' => User::find(Auth::user()->id)->mahasiswa->id,
                'kategori_kegiatan_id' => $request->kategori_kegiatan_id,
                'jenis_kegiatan_id' => $request->jenis_kegiatan_id,
                'nama_kegiatan' => $request->nama_kegiatan,
                'penyelenggara' => $request->penyelenggara,
                'tahun' => $request->tahun,
                'bukti' => $request->file('bukti')->store('bukti'),
                'valid_point' => '0',
            ]);

            $result = new PortofolioResource(
                Portofolio::findOrFail($portofolio->id)
            );

            $response = [
                'message' => "Portofolio Data created",
                'data' => $result,
            ];
            return response()->json($response, Response::HTTP_CREATED);
        } catch (QueryException $e) {
            return response()->json([
                'message' => "Failed " . $e->errorInfo,
            ]);
        }
    }

    public function show($id)
    {
        return new PortofolioResource(Portofolio::findOrFail($id));
    }

    public function byNim($nim)
    {
        $mhs_id = Mahasiswa::where('nim', $nim)->first()->id;
        return PortofolioResource::collection(
            Mahasiswa::find($mhs_id)->portofolio()->where('status', 0)->paginate(5)
        );
    }

    public function update(Request $request, $id)
    {
        $portofolio = Portofolio::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'kategori_kegiatan_id' => ['required'],
            'jenis_kegiatan_id' => ['required'],
            'nama_kegiatan' => ['required', 'string', 'max:191'],
            'penyelenggara' => ['required', 'string', 'max:191'],
            'tahun' => ['required', 'digits_between:4,4', 'numeric'],
            'bukti' => ['file', 'mimes:jpg,png,jpeg,pdf', 'max:2048']
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            if ($request->file('bukti')) {
                unlink('storage/' . $portofolio->bukti);
                $bukti = $request->file('bukti')->store('bukti');
            } else {
                $bukti = $portofolio->bukti;
            }
            $portofolio->update([
                'mahasiswa_id' => User::find(Auth::user()->id)->mahasiswa->id,
                'kategori_kegiatan_id' => $request->kategori_kegiatan_id,
                'jenis_kegiatan_id' => $request->jenis_kegiatan_id,
                'nama_kegiatan' => $request->nama_kegiatan,
                'penyelenggara' => $request->penyelenggara,
                'tahun' => $request->tahun,
                'bukti' => $bukti,
                'valid_point' => '0',
                'status' => '0',
            ]);

            $result = new PortofolioResource(
                Portofolio::findOrFail($portofolio->id)
            );

            $response = [
                'message' => "Portofolio Have Been Updated",
                'data' => $result,
            ];
            return response()->json($response, Response::HTTP_OK);
        } catch (QueryException $e) {
            return response()->json([
                'message' => "Failed " . $e->errorInfo,
            ]);
        }
    }

    public function validasi(Request $request, $id)
    {
        $portofolio = Portofolio::findOrFail($id);
        $request->validate([
            'valid_point' => ['required', 'numeric'],
        ]);

        try {
            $portofolio->valid_point = $request->valid_point;
            $portofolio->status = 1;
            $portofolio->save();
            $response = [
                'message' => "Portofolio Tervalidasi",
            ];
            return response()->json($response, Response::HTTP_OK);
        } catch (QueryException $e) {
            return response()->json([
                'message' => "Failed " . $e->errorInfo,
            ]);
        }
    }

    public function destroy($id)
    {
        $portofolio = Portofolio::findOrFail($id);
        try {
            unlink('storage/' . $portofolio->bukti);
            $portofolio->delete();
            $response = [
                'message' => "Portofolio Deleted",
            ];
            return response()->json($response, Response::HTTP_OK);
        } catch (QueryException $e) {
            return response()->json([
                'message' => "Failed " . $e->errorInfo,
            ]);
        }
    }
}

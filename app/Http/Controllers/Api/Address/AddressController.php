<?php

namespace App\Http\Controllers\Api\Address;

use App\Http\Controllers\Controller;
use Brick\Math\BigInteger;
use Illuminate\Http\Request;
use Laravolt\Indonesia\IndonesiaService;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District; 
use Laravolt\Indonesia\Models\Village;

class AddressController extends Controller
{
    protected IndonesiaService $indonesiaService;

    public function __construct(IndonesiaService $indonesiaService)
    {
        $this->indonesiaService = $indonesiaService;
    }

    public function getProvinces()
    {
        $provinces = $this->indonesiaService->allProvinces();
        return response()->json($provinces);
    }

    public function getCitiesByProvince(int $provinceId)
    {
        $province = $this->indonesiaService->findProvince($provinceId, ['cities']);
        return response()->json($province);
    }

    public function getDistrictsByCity(int $cityId)
    {
        $city = $this->indonesiaService->findCity($cityId, ['districts']);
        return response()->json($city);
    }

    public function getVillagesByDistrict(int $districtId)
    {
        $district = $this->indonesiaService->findDistrict($districtId, ['villages']);
        return response()->json($district);
    }

    /**
     * Match text-based address names to their corresponding database IDs.
     */
    public function matchAddressNames(Request $request)
    {
        $request->validate([
            'province' => ['required', 'string'],
            'city'     => ['required', 'string'],
            'district' => ['required', 'string'],
            'village'  => ['required', 'string'],
        ]);

        // Clean common prefixes/suffixes for more robust matching
        $provinceInput = trim(preg_replace('/(provinsi|prov)/i', '', $request->province));
        $cityInput     = trim(preg_replace('/(kabupaten|kab|kota)/i', '', $request->city));
        $districtInput = trim(preg_replace('/(kecamatan|kec)/i', '', $request->district));
        $villageInput  = trim(preg_replace('/(kelurahan|desa|desa\/kelurahan)/i', '', $request->village));

        // 1. Match Province
        $province = Province::where('name', 'like', '%' . $provinceInput . '%')->first();

        if (!$province) {
            return response()->json([
                'success' => false,
                'message' => 'Province not found',
                'data'    => null
            ], 404);
        }

        // 2. Match City/Regency
        $city = City::where('province_code', $province->code)
            ->where('name', 'like', '%' . $cityInput . '%')
            ->first();

        // 3. Match District
        $district = $city ? District::where('city_code', $city->code)
            ->where('name', 'like', '%' . $districtInput . '%')
            ->first() : null;

        // 4. Match Village
        $village = $district ? Village::where('district_code', $district->code)
            ->where('name', 'like', '%' . $villageInput . '%')
            ->first() : null;

        return response()->json([
            'success' => true,
            'message' => 'Address matched successfully',
            'data'    => [
                'province_id'   => $province->id,
                'province_name' => $province->name,
                'city_id'       => $city?->id,
                'city_name'     => $city?->name,
                'district_id'   => $district?->id,
                'district_name' => $district?->name,
                'village_id'    => $village?->id,
                'village_name'  => $village?->name,
            ]
        ]);
    }
}

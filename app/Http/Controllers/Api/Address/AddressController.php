<?php

namespace App\Http\Controllers\Api\Address;

use App\Http\Controllers\Controller;
use Brick\Math\BigInteger;
use Illuminate\Http\Request;
use Laravolt\Indonesia\IndonesiaService;

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
    
}

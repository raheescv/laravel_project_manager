public function getCustomerMeasurementSale($saleId)
{
    $rows = CustomerMeasurement::where('sale_id', $saleId)->get();
    return response()->json($rows);
}

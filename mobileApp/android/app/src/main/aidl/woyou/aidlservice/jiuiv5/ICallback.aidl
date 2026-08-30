package woyou.aidlservice.jiuiv5;

/** SUNMI inner-printer service callback. Vendor interface — do not reorder. */
interface ICallback {
	oneway void onRunResult(boolean isSuccess);
	oneway void onReturnString(String result);
	oneway void onRaiseException(int code, String msg);
	oneway void onPrintResult(int code, String msg);
}

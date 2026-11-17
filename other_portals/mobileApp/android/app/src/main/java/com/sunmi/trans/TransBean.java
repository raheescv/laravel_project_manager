package com.sunmi.trans;

import android.os.Parcel;
import android.os.Parcelable;

/**
 * Placeholder for the vendor type referenced by {@code IWoyouService.commitPrint}.
 *
 * The SUNMI AIDL is reproduced verbatim because a binder transaction id is just
 * the method's position in the interface — reordering or dropping a declaration
 * would silently call a *different* printer command. commitPrint is the only
 * method that needs this type and the app never calls it (we send ESC/POS
 * through sendRAWData), so an empty Parcelable is enough to let the AIDL
 * compile while keeping every transaction id correct.
 */
public class TransBean implements Parcelable {
    public TransBean() {}

    protected TransBean(Parcel in) {}

    @Override
    public void writeToParcel(Parcel dest, int flags) {}

    @Override
    public int describeContents() {
        return 0;
    }

    public static final Creator<TransBean> CREATOR = new Creator<TransBean>() {
        @Override
        public TransBean createFromParcel(Parcel in) {
            return new TransBean(in);
        }

        @Override
        public TransBean[] newArray(int size) {
            return new TransBean[size];
        }
    };
}

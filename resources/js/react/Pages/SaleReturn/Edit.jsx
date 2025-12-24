import React, { useEffect, useState } from "react";
import axios from "axios";
import CustomerSelect from "./Components/CustomerSelect";
import EmployeeSelectedit from "./Components/EmployeeSelectedit";
import CategorySelect from "./Components/CategorySelect";
import { toast } from "react-toastify"; 
import ProductGrid from "./ProductGrid";
import ViewItemsModal from "./Components/ViewItemsModal";
import AdvancePaymentModal from "../../Components/Booking/AdvancePaymentModal";
import { usePage } from "@inertiajs/react";


export default function Edit() {
    const [customerId, setCustomerId] = useState(null);
    const [products, setProducts] = useState([]);
    const [cartItems, setCartItems] = useState([]);
    const [viewModalOpen, setViewModalOpen] = useState(false);
    const [referenceNo, setReferenceNo] = useState("");
    const [discount, setDiscount] = useState(0);
    const [paymentMethod, setPaymentMethod] = useState("cash");
    const [showAdvanceModal, setShowAdvanceModal] = useState(false);
    const [customPaymentData, setCustomPaymentData] = useState(null);
    const [serviceCharge, setServiceCharge] = useState(0);



    const [employeeId, setEmployeeId] = useState(null);
     const [saleId, setSaleId] = useState(null); // null for new, or set existing sale id for update
    const [errors, setErrors] = useState({});

     const [categoryId, setCategoryId] = useState(null);
     const [measurements, setMeasurements] = useState([]);
     const [measurementValues, setMeasurementValues] = useState({});


    const subTotal = cartItems.reduce((sum, i) => sum + i.total, 0);
    const discountPercentage = subTotal ? (discount / subTotal) * 100 : 0;
    const grandTotal = subTotal - discount + serviceCharge;

    const totalQty = cartItems.reduce((s, i) => s + i.quantity, 0);


      const { saleData, customers, employees, categories, priceTypes, customerTypes, countries, paymentMethods, defaultProductType, defaultCustomerEnabled, defaultQuantity,service_charge } = usePage().props;
     useEffect(() => {
    if (!saleData) return;

   

    setSaleId(saleData.id);

    // Auto-select customer
    setCustomerId(saleData.account_id || null);

    // Auto-select employee
    setEmployeeId(Number(saleData.employee_id));

    // Auto-select category
    if (saleData.category_id) {
        setCategoryId(Number(saleData.category_id));
    }

    setDiscount(Number(saleData.other_discount || 0));
    setReferenceNo(saleData.reference_no || "");

    if (saleData.service_charge) {
        setServiceCharge(Number(saleData.service_charge));
    }
    // Load cart items
    if (saleData.items) {
        const items = Object.values(saleData.items).map(i => ({
            ...i,
            total: i.total,
        }));
        setCartItems(items);
    }

    // ✅ FIX PAYMENT METHOD
if (saleData.payment_method === 1) {
    setPaymentMethod("cash");
} else if (saleData.payment_method === 2) {
    setPaymentMethod("card");
} else if (saleData.payment_method === "custom") {
    setPaymentMethod("custom");
    setCustomPaymentData(saleData.custom_payment_data);
}


    console.log("SALE DATA 👉", saleData);
}, [saleData]);




    useEffect(() => {
        axios.get("/products/book").then((res) => {
            setProducts(
                res.data.map((p) => ({
                    ...p,
                      inventory_id: p.id,
                    quantity: p.stock,
                    image: p.image || "/logo.png",
                }))
            );
        });
    }, []);

  useEffect(() => {
    if (!categoryId) {
        setMeasurements([]);
        setMeasurementValues({});
        return;
    }

    axios
        .get(`/categories/measurements/${categoryId}`)
        .then((res) => {
            setMeasurements(res.data);

            const initialValues = {};
            res.data.forEach((m) => {
                initialValues[m.id] = "";
            });

            setMeasurementValues(initialValues);
        })
        .catch((err) => {
            console.error("Measurement load error:", err);
        });
}, [categoryId]);



useEffect(() => {
    if (!customerId || !categoryId) return;

    axios
        .get(`/categories/measurementscustomer/${customerId}/${categoryId}`)
        .then((res) => {
            if (res.data && Object.keys(res.data).length > 0) {
                setMeasurementValues((prev) => ({
                    ...prev,
                    ...res.data, // auto fill saved values
                }));
            }
        })
        .catch((err) => {
            console.error("Customer measurement load error:", err);
        });
}, [customerId, categoryId]);

    const handleAddToCart = (product) => {
        setCartItems((prev) => {
            const exists = prev.find((i) => i.id === product.id);
            if (exists) {
                return prev.map((i) =>
                    i.id === product.id
                        ? { ...i, quantity: i.quantity + 1, total: (i.quantity + 1) * i.unit_price }
                        : i
                );
            }
            return [
                ...prev,
                {
                    ...product,
                    quantity: 1,
                    unit_price: product.mrp,
                    discount: 0,
                    tax: 0,
                    total: product.mrp,
                },
            ];
        });
    };

    const handleSubmit = () => {
        console.log({ customerId, cartItems, referenceNo, discount, paymentMethod, grandTotal });
        alert("Submit logic here!");
    };

      const buildItemsPayload = () => {
        const items = {};
        cartItems.forEach((item) => {
            const key = `${employeeId}-${item.inventory_id}`;
            items[key] = {
                id: item.id || null,
                inventory_id: item.inventory_id,
                product_id: item.product_id,
                employee_id: employeeId,
                name: item.name,
                barcode: item.barcode || "",
                size: item.size || null,
                quantity: item.quantity,
                unit_price: item.unit_price,
                tax: 0,
                discount: 0,
                gross_amount: item.quantity * item.unit_price,
                net_amount: item.quantity * item.unit_price,
                tax_amount: 0,
                total: item.quantity * item.unit_price,
            };
        });
        return items;
    };


const validateSale = () => {
    if (!customerId) {
        toast.error("Please select customer");
        return false;
    }

    if (!employeeId) {
        toast.error("Please select employee");
        return false;
    }

    if (!categoryId) {
        toast.error("Please select category");
        return false;
    }

    if (cartItems.length === 0) {
        toast.error("Please add at least one product");
        return false;
    }

    if (discount < 0) {
        toast.error("Discount cannot be negative");
        return false;
    }

    if (discount > subTotal) {
        toast.error("Discount cannot exceed subtotal");
        return false;
    }

    // 🔴 MEASUREMENT REQUIRED VALIDATION
    if (measurements.length > 0) {
        for (let m of measurements) {
            if (!measurementValues[m.id] || measurementValues[m.id].trim() === "") {
                toast.error(`Please enter ${m.name}`);
                return false;
            }
        }
    }

    return true;
};


// 🔹 Build measurement payload
const buildMeasurementPayload = () => {
    return Object.keys(measurementValues)
        .filter((key) => measurementValues[key] !== "")
        .map((key) => ({
            measurement_template_id: Number(key),
            value: measurementValues[key],
        }));
};

    /* ------------------- Submit / Update sale ------------------- */
 const handleSave = async () => {
    if (!validateSale()) return;

    const payload = {
        id: saleId,
        date: new Date().toISOString().slice(0, 10),
        employee_id: employeeId,
        sale_type: "normal",
        account_id: customerId,
        customer_mobile: "",
        other_discount: discount,
        round_off: grandTotal - (subTotal - discount),
        gross_amount: subTotal,
        item_discount: 0,
        tax_amount: 0,
        total: subTotal - discount,
        grand_total: grandTotal,
        service_charge: serviceCharge || 0,


        category_id: categoryId,
        measurements: buildMeasurementPayload(),

        items: buildItemsPayload(),
        comboOffers: [],
        payment_method:
            paymentMethod === "cash"
                ? 1
                : paymentMethod === "card"
                ? 2
                : "custom",

        send_to_whatsapp: false,
        rating: 0,
        feedback_type: "compliment",
        feedback: null,
        type: "booking",
    };

    // ✅ ADVANCE PAYMENT FIX (SAFE)
    if (paymentMethod === "custom" && customPaymentData) {
    payload.custom_payment_data = {
        payments: customPaymentData.payments,
    };

    payload.status =
        customPaymentData.balanceDue > 0 ? "draft" : "completed";
} else {
    payload.custom_payment_data = null;
    payload.status = "completed";
}


    console.log("FINAL PAYLOAD 👉", payload);

    try {
        const res = await axios.post("/pos/submit", payload);

        toast.success("Sale submitted successfully 🎉");

        if (res.data?.sale_id) {
            window.open(`/print/sale/invoice/${res.data.sale_id}`, "_blank");
        }

        setTimeout(() => {
            window.location.href = "/sale/create-booking";
        }, 1500);

    } catch (e) {
        toast.error(e.response?.data?.message || "Failed to save sale ❌");
    }
};



    return (
        <div className="main-wrapper">
            <div className="page-wrapper pos-pg-wrapper ms-0">
                <div className="content pos-design p-0">
                    <div className="row pos-wrapper align-items-start">

                        {/* LEFT SIDE: Product Grid */}
                        <div className="col-md-12 col-lg-7">
                            <CustomerSelect
                                value={customerId}
                                onChange={setCustomerId}
                            />

                                            <div className="mb-2">
                    <label className="fw-bold mb-1"></label>
                   <CategorySelect 
    value={categoryId}           // pass existing sale category
    onChange={(value) => setCategoryId(Number(value))} 
/>


                    </div>

                    {measurements.length > 0 && (
    <div className="card mt-2 p-2">
        <h6 className="fw-bold mb-2">Measurements</h6>

        {measurements.map((m) => (
            <div key={m.id} className="mb-2">
                <label className="form-label">{m.name}</label>
                <input
                    type="text"
                    className="form-control form-control-sm"
                    placeholder={`Enter ${m.name}`}
                    value={measurementValues[m.id] || ""}
                    onChange={(e) =>
                        setMeasurementValues({
                            ...measurementValues,
                            [m.id]: e.target.value
                        })
                    }
                />
            </div>
        ))}
    </div>
)}


<EmployeeSelectedit
    value={employeeId ?? null}
    onChange={setEmployeeId}
/>


                            <div
                                className="tabs_container"
                                style={{ height: "80vh", overflow: "auto", overflowX: "hidden", paddingRight: "10px" }}
                            >
                                <div className="tab_content active">
                                    <ProductGrid products={products} onAddToCart={handleAddToCart} />
                                </div>
                            </div>
                        </div>

                        {/* RIGHT SIDE: Cart + Order Summary */}
                        <div className="col-md-12 col-lg-5 ps-0">
                            <aside className="product-order-list">
                                {/* Cart Header */}
                                <div className="cart-summary mb-3">
                                    <div className="d-flex align-items-center justify-content-between bg-white p-3 rounded shadow-sm">
                                        <div className="d-flex align-items-center gap-3">
                                            <span className="cart-badge">{totalQty}</span>
                                            <div>
                                                <h6 className="mb-0">Cart Items</h6>
                                                <small className="text-muted">{totalQty} items in cart</small>
                                            </div>
                                        </div>
                                        {totalQty > 0 && (
                                            <div className="d-flex gap-2">
                                                <div className="d-flex flex-column align-items-center">
                                                    <button type="button" className="action-btn view-btn" onClick={() => setViewModalOpen(true)}>
                                                        <i className="fa fa-list"></i>
                                                    </button>
                                                    <small>View</small>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Cart Items */}
                                <div className="product-wrap mb-3">
                                    {cartItems.map((item, index) => (
                                        <div
                                            key={item.id}
                                            className={`product-list d-flex align-items-center justify-content-between ${index % 2 ? "bg-custom-gray" : ""}`}
                                        >
                                            <div>
                                                <h6>{item.name}</h6>
                                                <p className="text-success">₹{item.total.toFixed(2)}</p>
                                            </div>
                                            <div className="qty-item">
                                                <span>{item.quantity}</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Reference & Discount */}
                                <div className="mb-2">
                                    <label className="fw-bold mb-1">Reference No</label>
                                    <input
                                        type="text"
                                        className="form-control form-control-sm"
                                        placeholder="Enter reference number"
                                        value={referenceNo}
                                        onChange={(e) => setReferenceNo(e.target.value)}
                                    />
                                </div>
                                <div className="mb-2">
                                    <label className="fw-bold mb-1">Discount Amount</label>
                                    <input
                                        type="number"
                                        className="form-control form-control-sm"
                                        placeholder="Enter discount amount"
                                        value={discount}
                                        onChange={(e) => setDiscount(parseFloat(e.target.value) || 0)}
                                    />
                                </div>

                                                                    <div className="mb-2">
                                        <label className="fw-bold mb-1">
                                            Service Charge <small className="text-muted">(optional)</small>
                                        </label>
                                        <input
                                            type="number"
                                            className="form-control form-control-sm"
                                            placeholder="Enter service charge"
                                            value={serviceCharge || ""}
                                            onChange={(e) =>
                                                setServiceCharge(Number(e.target.value) || 0)
                                            }
                                        />
                                    </div>


                                {/* Totals */}
                                <table className="table table-borderless mb-3">
                                    <tbody>
                                        <tr>
                                            <td className="text-muted">Sub Total</td>
                                            <td className="text-end fw-bold">₹{subTotal.toFixed(2)}</td>
                                        </tr>
                                        <tr>
                                            <td className="text-danger">Discount ({discountPercentage.toFixed(2)}%)</td>
                                            <td className="text-end text-danger">-₹{discount.toFixed(2)}</td>
                                        </tr>
                                                                                    {serviceCharge > 0 && (
                                                <tr>
                                                    <td className="text-muted">Service Charge</td>
                                                    <td className="text-end fw-bold">₹{serviceCharge.toFixed(2)}</td>
                                                </tr>
                                            )}

                                        <tr className="border-top">
                                            <td className="fw-bold">Total</td>
                                            <td className="text-end fw-bold text-success">₹{grandTotal.toFixed(2)}</td>
                                        </tr>
                                    </tbody>
                                </table>

                                {paymentMethod === "custom" && customPaymentData && (
    <table className="table table-borderless mb-3">
        <tbody>
            <tr>
                <td className="text-success fw-bold">Paid</td>
                <td className="text-end text-success fw-bold">
                    ₹{customPaymentData.totalPaid.toFixed(2)}
                </td>
            </tr>
            <tr>
                <td className="text-danger fw-bold">Balance</td>
                <td className="text-end text-danger fw-bold">
                    ₹{customPaymentData.balanceDue.toFixed(2)}
                </td>
            </tr>
        </tbody>
    </table>
)}



                                {/* Payment Method */}
                                <div className="payment-method mb-3">
                                    <h6>Payment Method</h6>
                                    <div className="d-flex gap-2">
                                        {["cash", "card", "custom"].map((method) => (
                                            <button
                                                key={method}
                                                className={`btn btn-outline-primary ${paymentMethod === method ? "active" : ""}`}
                                               onClick={() => {
    setPaymentMethod(method);

    if (method === "custom") {
        setShowAdvanceModal(true);
    }
}}

                                            >
                                                {method === "card" ? "Card" : method === "custom" ? "Advance Pay" : "Cash"}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {/* Submit */}
                                <button className="btn btn-primary w-100" onClick={handleSave}>
                        {saleId ? "Update Sale" : "Submit"}
                    </button>
                            </aside>
                        </div>

                    </div>
                </div>
            </div>

            {/* Modal */}
            {viewModalOpen && (
                <ViewItemsModal
                    items={cartItems}
                    onClose={() => setViewModalOpen(false)}
                    onUpdate={(item) => setCartItems((prev) => prev.map((i) => (i.id === item.id ? item : i)))}
                    onRemove={(id) => setCartItems((prev) => prev.filter((i) => i.id !== id))}
                />
            )}
           <AdvancePaymentModal
    open={showAdvanceModal}
    onClose={() => setShowAdvanceModal(false)}
    grandTotal={grandTotal}
    onSave={(data) => setCustomPaymentData(data)}
/>


        </div>
    );
}

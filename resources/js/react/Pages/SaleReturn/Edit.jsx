import React, { useEffect, useState } from "react";
import axios from "axios";
import CustomerSelect from "./Components/CustomerSelect";
import EmployeeSelectedit from "./Components/EmployeeSelectedit";
import CategorySelect from "./Components/CategorySelect";
import CategorySidebar from "./Components/CategorySidebar";
import TopMenu from "./Components/TopMenu";
import { toast } from "react-toastify"; 
import ProductGrid from "./ProductGrid";
import ViewItemsModal from "./Components/ViewItemsModal";
import AdvanceeditPaymentModal from "../../Components/Booking/AdvanceeditPaymentModal";
import { usePage } from "@inertiajs/react";
import CustomerDetailsModal from "./Components/CustomerDetailsModal";
import AddCustomerModal from "./Components/AddCustomerModal";
import SubCategorySelect from "./Components/SubCategorySelect"; // <-- new import
// customer details card
import { FaPen } from "react-icons/fa";

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
    const [showAddCustomerModal, setShowAddCustomerModal] = useState(false);
    const [refreshCustomerKey, setRefreshCustomerKey] = useState(0);
    const [addedCustomer, setAddedCustomer] = useState(null);
    const [subCategoryIds, setSubCategoryIds] = useState([]); // <-- track selected subcategories (array)
    const [widthValue, setWidthValue] = useState(""); // Track width input
    const [sizeValue, setSizeValue] = useState("");  
      
      




    const [employeeId, setEmployeeId] = useState(null);
     const [saleId, setSaleId] = useState(null); // null for new, or set existing sale id for update
    const [errors, setErrors] = useState({});

    const [employeesList, setEmployeesList] = useState([]);
    const [selectedEmployee, setSelectedEmployee] = useState(null);
    const [customerDetails, setCustomerDetails] = useState(null);
    const [showCustomerModal, setShowCustomerModal] = useState(false);

    // Inline edit modal state (no new component)
    const [editingModalOpen, setEditingModalOpen] = useState(false);
    const [editingItem, setEditingItem] = useState(null);
    const [editingValues, setEditingValues] = useState({ quantity: 1, unit_price: 0 });

    const [selectedCategoryIds, setSelectedCategoryIds] = useState([]);
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

    // Auto-select category(s)
    if (saleData.category_ids && Array.isArray(saleData.category_ids) && saleData.category_ids.length > 0) {
        setSelectedCategoryIds(saleData.category_ids.map((v) => Number(v)));
    } else if (saleData.category_id) {
        // category_id may be a CSV string or single id
        if (typeof saleData.category_id === 'string' && saleData.category_id.indexOf(',') !== -1) {
            setSelectedCategoryIds(saleData.category_id.split(',').map((v) => Number(v.trim())));
        } else {
            setSelectedCategoryIds([Number(saleData.category_id)]);
        }
    } else {
        setSelectedCategoryIds([]);
    }

    setWidthValue(saleData.width || "");
    setSizeValue(saleData.size || "");

    // Auto-select sub-category(s)
    if (saleData.sub_category_ids && Array.isArray(saleData.sub_category_ids) && saleData.sub_category_ids.length > 0) {
        setSubCategoryIds(saleData.sub_category_ids.map((v) => Number(v)));
    } else if (saleData.sub_category_id) {
        if (typeof saleData.sub_category_id === 'string' && saleData.sub_category_id.indexOf(',') !== -1) {
            setSubCategoryIds(saleData.sub_category_id.split(',').map((v) => Number(v.trim())));
        } else {
            setSubCategoryIds([Number(saleData.sub_category_id)]);
        }
    } else {
        setSubCategoryIds([]);
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
        let mounted = true;
        axios.get('/employees/employee')
            .then(res => {
                if (!mounted) return;
                setEmployeesList(res.data || []);
            })
            .catch(() => setEmployeesList([]));
        return () => { mounted = false };
    }, []);

    useEffect(() => {
        if (!employeeId) {
            setSelectedEmployee(null);
            return;
        }
        const emp = employeesList.find(e => Number(e.id) === Number(employeeId)) || null;
        setSelectedEmployee(emp ? { name: emp.name, email: emp.email } : null);
    }, [employeeId, employeesList]);

    // load selected customer details
    useEffect(() => {
        let mounted = true;
        if (!customerId) {
            setCustomerDetails(null);
            return;
        }
        axios.get(`/account/customer/${customerId}/details`)
            .then((res) => {
                if (!mounted) return;
                if (res.data && res.data.success) {
                    setCustomerDetails(res.data);
                } else {
                    setCustomerDetails(null);
                }
            })
            .catch(() => setCustomerDetails(null));

        return () => { mounted = false };
    }, [customerId]);

  useEffect(() => {
    if (!selectedCategoryIds || selectedCategoryIds.length === 0) {
        setMeasurements([]);
        setMeasurementValues({});
        return;
    }

    const requests = selectedCategoryIds.map(cid => axios.get(`/categories/measurements/${cid}`));
    Promise.all(requests)
        .then(responses => {
            const merged = [];
            const seen = new Set();
            responses.forEach(r => {
                (r.data || []).forEach(m => {
                    if (!seen.has(m.id)) {
                        seen.add(m.id);
                        merged.push(m);
                    }
                });
            });

            setMeasurements(merged);

            const initialValues = {};
            merged.forEach((m) => {
                initialValues[m.id] = "";
            });

            setMeasurementValues(initialValues);
        })
        .catch((err) => {
            console.error("Measurement load error:", err);
            setMeasurements([]);
            setMeasurementValues({});
        });
}, [selectedCategoryIds]);



useEffect(() => {
    if (!customerId || !selectedCategoryIds || selectedCategoryIds.length === 0) return;

    const requests = selectedCategoryIds.map(cid => axios.get(`/categories/measurementscustomer/${customerId}/${cid}`));
    Promise.all(requests)
        .then(responses => {
            const merged = {};
            responses.forEach(r => {
                if (r.data && typeof r.data === 'object') {
                    Object.assign(merged, r.data);
                }
            });

            if (Object.keys(merged).length > 0) {
                setMeasurementValues((prev) => ({
                    ...prev,
                    ...merged,
                }));
            }
        })
        .catch((err) => {
            console.error("Customer measurement load error:", err);
        });
}, [customerId, selectedCategoryIds]);

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

    if (!selectedCategoryIds || selectedCategoryIds.length === 0) {
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
    if (selectedCategoryIds.length === 1 && (!subCategoryIds || subCategoryIds.length === 0)) {
            toast.error("Please select subcategory");
            return false;
        }
    
        if (!widthValue || widthValue.trim() === "") {
            toast.error("Please enter width");
            return false;
        }
    
        if (!sizeValue || sizeValue.trim() === "") {
            toast.error("Please select size");
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

        // single-category backward compatibility + arrays for multi-select
        category_id: selectedCategoryIds.length === 1 ? selectedCategoryIds[0] : null,
        category_ids: selectedCategoryIds,
        sub_category_id: subCategoryIds.length === 1 ? subCategoryIds[0] : null,
        sub_category_ids: subCategoryIds,
        width: widthValue,  // <-- added
        size: sizeValue,  
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
// ✅ ADVANCE PAYMENT FIX (FINAL)
if (paymentMethod === "custom" && customPaymentData) {

    const balance = Number(customPaymentData.balanceDue || 0);
    const totalPaid = Number(customPaymentData.totalPaid || 0);

    // 🔴 If fully paid, force full amount
    if (balance === 0) {
        payload.custom_payment_data = {
            payments: [
                {
                    payment_method_id:
                        customPaymentData.payments?.[0]?.payment_method_id || 1,
                    amount: grandTotal, // ✅ FULL AMOUNT
                },
            ],
        };
        payload.status = "completed";
    } 
    // 🟡 Partial payment
    else {
        payload.custom_payment_data = {
            payments: customPaymentData.payments,
        };
        payload.status = "draft";
    }

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



    const primaryCategoryId = selectedCategoryIds && selectedCategoryIds.length > 0 ? selectedCategoryIds[0] : null;

    return (
        <div className="main-wrapper">
            <div className="page-wrapper pos-pg-wrapper ms-0">
                <TopMenu onHomeClick={() => window.location.href = '/'} />
                <div className="content pos-design p-0">
                    <div className="row pos-wrapper align-items-start">

                    {/* LEFT SIDEBAR: Categories */}
                    <div className="col-12 col-lg-2 pe-0">
                        <CategorySidebar selectedId={selectedCategoryIds} onSelect={(ids) => setSelectedCategoryIds(ids)} />
                    </div>

                    

                        {/* CENTER: Product Grid */}
                       <div className="col-md-12 col-lg-7">


                             <div className="mb-2">
                                                           <label className="fw-bold mb-1">Customer</label>
                                                           <div className="d-flex gap-2 align-items-center">
                                                               <div style={{ flex: 1 }}>
                                                                    <CustomerSelect
                                                                       value={customerId}
                                                                       onChange={setCustomerId}
                                                                       newCustomer={addedCustomer} // <-- use this instead of customerDetails
                                                                   />
                                                               </div>
                                                               <div>
                                                                   <div className="d-flex gap-2">
                                                                       <button type="button" className="btn btn-sm btn-outline-success" title="Add new customer" onClick={() => setShowAddCustomerModal(true)}>+ Add New customer</button>
                                                                       <button
                                                                           type="button"
                                                                           className="btn btn-sm btn-outline-secondary"
                                                                           title="View / Edit customer"
                                                                           onClick={() => setShowCustomerModal(true)}
                                                                           disabled={!customerId}
                                                                       >
                                                                           View
                                                                       </button>
                                                                   </div>
                                                               </div>
                                                           </div>
                                                       </div>
                                                       



                                                          {primaryCategoryId && (
                                                                                       <div className="mb-2">
                                                                                            
                                                                                           <SubCategorySelect
                                                                                               categoryId={selectedCategoryIds}
                                                                                               selectedSubId={subCategoryIds}
                                                                                               onSelect={setSubCategoryIds}
                                                                                           />
                                                       
                                                       
                                                                                             <div className="mt-2">
                                                                   <label className="form-label">Width</label>
                                                                  <input
                                                                       type="number"
                                                                       className="form-control form-control-sm"
                                                                       value={widthValue}
                                                                       onChange={(e) => setWidthValue(e.target.value)}
                                                                       />
                                                               </div>
                                                       
                                                               {/* Size dropdown */}
                                                               <div className="mt-2">
                                                                   <label className="form-label">Size</label>
                                                                               <select
                                                                   className="form-select form-select-sm"
                                                                   value={sizeValue}
                                                                   onChange={(e) => setSizeValue(e.target.value)}
                                                                   >
                                                                       <option value="">Select Size</option>
                                                                       <option value="S">S</option>
                                                                       <option value="M">M</option>
                                                                       <option value="L">L</option>
                                                                       <option value="XL">XL</option>
                                                                       <option value="XXL">XXL</option>
                                                                       <option value="XXXL">XXXL</option>
                                                                       <option value="XXXXL">XXXXL</option>
                                                                   </select>
                                                               </div>
                                                                                       </div>
                                                       
                                                                                       
                                                       
                                                       
                                                       
                                                                                   )}
                                                  

                    {measurements.length > 0 && (
  <div className="card mt-2 p-2">
    <h6 className="fw-bold mb-2">Measurements</h6>
    <div className="row">
      {measurements.map((m, index) => (
        <div key={m.id} className="col-md-4 mb-2"> {/* 3 columns per row */}
          <label className="form-label">{m.name}</label>
          <input
            type="text"
            className="form-control form-control-sm"
            placeholder={`Enter ${m.name}`}
            value={measurementValues[m.id] || ""}
            onChange={(e) =>
              setMeasurementValues({
                ...measurementValues,
                [m.id]: e.target.value,
              })
            }
          />
        </div>
      ))}
    </div>
  </div>
)}


                            <div className="mb-2">
                                <label className="fw-bold mb-1">Employee</label>
                                <EmployeeSelectedit value={employeeId ?? null} onChange={setEmployeeId} />
                            </div>


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
                        <div className="col-md-12 col-lg-3 ps-0">
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
                                            <div className="d-flex align-items-center gap-2">
                                                <div className="qty-item me-2">
                                                    <span>{item.quantity}</span>
                                                </div>
                                                <div className="d-flex gap-1">
                                                    <button
                                                        type="button"
                                                        className="btn btn-outline-secondary btn-sm d-inline-flex align-items-center justify-content-center"
                                                        title="Edit item"
                                                        aria-label="Edit item"
                                                        style={{ width: 28, height: 28, padding: 0 }}
                                                        onClick={() => {
                                                            setEditingItem(item);
                                                            setEditingValues({ quantity: item.quantity, unit_price: item.unit_price });
                                                            setEditingModalOpen(true);
                                                        }}
                                                    >
                                                       <FaPen size={12} />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        className="btn btn-sm btn-outline-danger p-1"
                                                        title="Delete item"
                                                        aria-label="Delete item"
                                                        onClick={() => {
                                                            if (window.confirm('Remove this item from cart?')) {
                                                                setCartItems((prev) => prev.filter((i) => i.id !== item.id));
                                                            }
                                                        }}
                                                    >
                                                        <i className="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Edit Item Modal (rendered inline) */}
                                {editingModalOpen && (
                                    <>
                                        <div className="modal show d-block" tabIndex="-1" role="dialog">
                                            <div className="modal-dialog" role="document">
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title">Edit Item</h5>
                                                        <button type="button" className="btn-close" aria-label="Close" onClick={() => setEditingModalOpen(false)}></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        {editingItem && (
                                                            <div>
                                                                <div className="mb-2">
                                                                    <label className="form-label">Product</label>
                                                                    <input type="text" className="form-control form-control-sm" value={editingItem.name} readOnly />
                                                                </div>
                                                                <div className="row g-2">
                                                                    <div className="col-6">
                                                                        <label className="form-label">Quantity</label>
                                                                        <input
                                                                            type="number"
                                                                            className="form-control form-control-sm"
                                                                            min={1}
                                                                            value={editingValues.quantity}
                                                                            onChange={(e) => setEditingValues((v) => ({ ...v, quantity: Number(e.target.value) || 1 }))}
                                                                        />
                                                                    </div>
                                                                    <div className="col-6">
                                                                        <label className="form-label">Unit Price</label>
                                                                        <input
                                                                            type="number"
                                                                            className="form-control form-control-sm"
                                                                            min={0}
                                                                            step="0.01"
                                                                            value={editingValues.unit_price}
                                                                            onChange={(e) => setEditingValues((v) => ({ ...v, unit_price: Number(e.target.value) || 0 }))}
                                                                        />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="modal-footer">
                                                        <div className="d-flex w-100 justify-content-between">
                                                            <div>
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-danger btn-sm p-1 me-2"
                                                                    title="Delete item"
                                                                    aria-label="Delete item"
                                                                    onClick={() => {
                                                                        if (!editingItem) return;
                                                                        if (window.confirm('Remove this item from cart?')) {
                                                                            setCartItems((prev) => prev.filter((i) => i.id !== editingItem.id));
                                                                            setEditingModalOpen(false);
                                                                            setEditingItem(null);
                                                                        }
                                                                    }}
                                                                >
                                                                    <i className="fa fa-trash"></i>
                                                                </button>
                                                            </div>
                                                            <div>
                                                                <button type="button" className="btn btn-secondary me-2" onClick={() => setEditingModalOpen(false)}>Cancel</button>
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-primary"
                                                                    onClick={() => {
                                                                        // Save changes
                                                                        if (!editingItem) return;
                                                                        const q = Number(editingValues.quantity) || 1;
                                                                        const up = Number(editingValues.unit_price) || 0;
                                                                        setCartItems((prev) => prev.map((i) => i.id === editingItem.id ? { ...i, quantity: q, unit_price: up, total: q * up } : i));
                                                                        setEditingModalOpen(false);
                                                                        setEditingItem(null);
                                                                    }}
                                                                >
                                                                    Save changes
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="modal-backdrop fade show"></div>
                                    </>
                                )}

                                {/* Reference & Discount */}
                                    
                                    {showCustomerModal && (
                                        <CustomerDetailsModal
                                            open={showCustomerModal}
                                            onClose={() => setShowCustomerModal(false)}
                                            customerId={customerId}
                                            initialDetails={customerDetails}
                                            onSaved={(updated) => {
                                                if (updated && updated.id) setCustomerId(updated.id);
                                                setCustomerDetails((prev) => ({ ...prev, customer: updated }));
                                                setShowCustomerModal(false);
                                            }}
                                        />
                                    )}
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
                        {saleId ? "Update" : "Submit"}
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
                    employee={selectedEmployee}
                />
            )}

             {showAddCustomerModal && (
                <AddCustomerModal
                   open={showAddCustomerModal}
                   onClose={() => setShowAddCustomerModal(false)}
                   onSaved={(customer) => {
                       setCustomerId(customer.id);         // Select the new customer
                       setAddedCustomer(customer);         // Trigger CustomerSelect to add it
                       setShowAddCustomerModal(false);     // Close modal
                   }}
               />
            )}
      <AdvanceeditPaymentModal
    open={showAdvanceModal}
    onClose={() => setShowAdvanceModal(false)}
    fullTotal={subTotal - discount + serviceCharge}   // ORIGINAL TOTAL
    alreadyPaid={customPaymentData?.totalPaid || 0} // EDIT MODE SUPPORT
    onSave={(data) => setCustomPaymentData(data)}
/>


        </div>
    );
}

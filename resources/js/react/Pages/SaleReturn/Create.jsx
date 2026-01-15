import React, { useEffect, useState } from "react";
import axios from "axios";
import CustomerSelect from "./Components/CustomerSelect";
import EmployeeSelect from "./Components/EmployeeSelect";
import CategorySelect from "./Components/CategorySelect";
import CategorySidebar from "./Components/CategorySidebar";
import MainCategorySidebar from "./MainCategorySidebar";
import TopMenu from "./Components/TopMenu";
import { toast } from "react-toastify"; 
import ProductGrid from "./ProductGrid";
import ViewItemsModal from "./Components/ViewItemsModal";
import AdvancePaymentModal from "../../Components/Booking/AdvancePaymentModal";
import { usePage } from "@inertiajs/react";
import { FaPen } from "react-icons/fa";
import CustomerDetailsModal from "./Components/CustomerDetailsModal";
import AddCustomerModal from "./Components/AddCustomerModal";
import SubCategorySelect from "./Components/SubCategorySelect"; // <-- new import




// show selected customer details (from server) in create page

export default function Create() {
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
    const [subCategoryId, setSubCategoryId] = useState(null); // <-- track selected subcategory (single)
    const [widthValues, setWidthValues] = useState({}); // { subCategoryId: [width1, width2, ...] }
    const [sizeValues, setSizeValues] = useState({}); // { subCategoryId: [size1, size2, ...] }
    // Track how many width/size groups per subcategory
    const [widthSizeCounts, setWidthSizeCounts] = useState({}); // { subCategoryId: count }
    // Track how many measurement groups per subcategory
    const [measurementCounts, setMeasurementCounts] = useState({}); // { subCategoryId: count }





    const [employeeId, setEmployeeId] = useState(null);
     const [saleId, setSaleId] = useState(null); // null for new, or set existing sale id for update
    const [errors, setErrors] = useState({});
    const [employeesList, setEmployeesList] = useState([]);
    const [selectedEmployee, setSelectedEmployee] = useState(null);
    const [customerDetails, setCustomerDetails] = useState(null);
    const [showCustomerModal, setShowCustomerModal] = useState(false);

    const { props } = usePage();

    // Edit modal state (kept inside this file per request)
    const [editingModalOpen, setEditingModalOpen] = useState(false);
    const [editingItem, setEditingItem] = useState(null);
    const [editingValues, setEditingValues] = useState({ quantity: 1, unit_price: 0 });

    const [selectedCategoryId, setSelectedCategoryId] = useState(null); // for measurement category (CategorySidebar)
    const [selectedMainCategoryIds, setSelectedMainCategoryIds] = useState([]); // for main categories (MainCategorySidebar)
    const [availableSubCategories, setAvailableSubCategories] = useState({}); // id -> {id,name,measurement_category_id}
    const [measurementsInstances, setMeasurementsInstances] = useState([]); // duplicated per subcategory
    const [measurementValues, setMeasurementValues] = useState({}); // keys: `${subId}-${measurementId}`


    const subTotal = cartItems.reduce((sum, i) => sum + i.total, 0);
    const discountPercentage = subTotal ? (discount / subTotal) * 100 : 0;
    const grandTotal = subTotal - discount + serviceCharge;

    const totalQty = cartItems.reduce((s, i) => s + i.quantity, 0);

    // Fetch products when selectedMainCategoryIds changes
    useEffect(() => {
        // If no main category selected, fetch all
        const params = selectedMainCategoryIds.length > 0 ? { main_category_id: selectedMainCategoryIds } : {};
        axios.get("/products/book", { params }).then((res) => {
            setProducts(
                res.data.map((p) => ({
                    ...p,
                    inventory_id: p.id,
                    quantity: p.stock,
                    image: p.image || "/logo.png",
                }))
            );
        });
    }, [selectedMainCategoryIds]);

    // load employees once so we can show selected employee details in modals/sidebar
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

    // update selectedEmployee when employeeId or employeesList changes
    useEffect(() => {
        if (!employeeId) {
            setSelectedEmployee(null);
            return;
        }
        const emp = employeesList.find(e => Number(e.id) === Number(employeeId)) || null;
        setSelectedEmployee(emp ? { name: emp.name, email: emp.email } : null);
    }, [employeeId, employeesList]);

    // set initial customer id from server props if provided
    useEffect(() => {
        if (props?.saleData?.account_id) {
            setCustomerId(props.saleData.account_id);
        } else if (props?.customers && Object.keys(props.customers).length > 0 && !customerId) {
            // if default customer provided in props, pick it
            const firstKey = Object.keys(props.customers)[0];
            setCustomerId(props.customers[firstKey].id ?? null);
        }
    }, [props]);

    // load selected customer details for side-card
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

    // Fetch available subcategories for selected categories (used for labels and mapping)
    useEffect(() => {
        if (!selectedCategoryId) {
            setAvailableSubCategories({});
            setSubCategoryId(null);
            setWidthValues({});
            setSizeValues({});
            setMeasurementCounts({});
            setMeasurementsInstances([]);
            setMeasurementValues({});
            return;
        }

        const url = `/categories/categories/measurement/${selectedCategoryId}/subcategories`;
        axios.get(url)
            .then(res => {
                const list = res.data || [];
                const map = {};
                list.forEach(s => { map[s.id] = s; });
                setAvailableSubCategories(map);
                // If selected subcategory is not valid, reset
                if (!map[subCategoryId]) setSubCategoryId(null);
            })
            .catch(() => {
                setAvailableSubCategories({});
                setSubCategoryId(null);
                setWidthValues({});
                setSizeValues({});
                setMeasurementCounts({});
                setMeasurementsInstances([]);
                setMeasurementValues({});
            });
    }, [selectedCategoryId]);


    // When subcategories (models) change, build per-model measurement instances and init width/size keys
    useEffect(() => {
        if (!subCategoryId) {
            setMeasurementsInstances([]);
            setMeasurementValues(prev => {
                // clear any keys that were instance keys
                const next = { ...prev };
                Object.keys(next).forEach(k => { if (String(k).indexOf('-') !== -1) delete next[k]; });
                return next;
            });
            setWidthValues({});
            setSizeValues({});
            return;
        }

        // ensure width/size keys exist for selected subcategory
        setWidthValues(prev => {
            const next = { ...prev };
            if (!(subCategoryId in next)) next[subCategoryId] = [''];
            else if (!Array.isArray(next[subCategoryId])) next[subCategoryId] = [next[subCategoryId]];
            return next;
        });
        setSizeValues(prev => {
            const next = { ...prev };
            if (!(subCategoryId in next)) next[subCategoryId] = [''];
            else if (!Array.isArray(next[subCategoryId])) next[subCategoryId] = [next[subCategoryId]];
            return next;
        });
        setMeasurementCounts(prev => {
            const next = { ...prev };
            if (!(subCategoryId in next)) next[subCategoryId] = 1;
            return next;
        });

        // Determine category id for measurement templates
        const cid = availableSubCategories[subCategoryId]?.measurement_category_id || selectedCategoryId || null;
        if (!cid) {
            setMeasurementsInstances([]);
            return;
        }
        axios.get(`/categories/measurements/${cid}`)
            .then(r => {
                const templates = r.data || [];
                const sub = availableSubCategories[subCategoryId] || {};
                const instances = templates.map(t => ({
                    id: t.id,
                    name: t.name,
                    subcategory_id: subCategoryId,
                    subcategory_name: sub.name || `Model ${subCategoryId}`,
                    category_id: cid,
                    instanceKey: `${subCategoryId}-${t.id}`,
                    values: t.values || t.template_values || '' // ensure values field is present for dropdown logic
                }));
                setMeasurementsInstances(instances);
                setMeasurementValues(prev => {
                    const next = { ...prev };
                    instances.forEach(inst => { if (!(inst.instanceKey in next)) next[inst.instanceKey] = "" });
                    return next;
                });
            })
            .catch(err => {
                console.error('Measurement instances load error:', err);
                setMeasurementsInstances([]);
            });
    }, [subCategoryId, availableSubCategories, selectedCategoryId]);



// Load customer-specific measurement defaults and apply to instances
    useEffect(() => {
        if (!customerId || measurementsInstances.length === 0) return;
        const cid = measurementsInstances[0]?.category_id;
        if (!cid) return;
        axios.get(`/categories/measurementscustomer/${customerId}/${cid}`)
            .then(r => {
                const merged = r.data || {};
                if (Object.keys(merged).length > 0) {
                    setMeasurementValues(prev => {
                        const next = { ...prev };
                        measurementsInstances.forEach(inst => {
                            const tplId = String(inst.id);
                            if (merged[tplId] !== undefined && (next[inst.instanceKey] === undefined || next[inst.instanceKey] === '')) {
                                next[inst.instanceKey] = merged[tplId];
                            }
                        });
                        return next;
                    });
                }
            })
            .catch(err => console.error('Customer measurement load error:', err));
    }, [customerId, measurementsInstances]);

    const handleAddToCart = (product) => {
        setCartItems((prev) => {
            const exists = prev.find((i) => i.id === product.id);
            // Get category and subcategory names from current selection
            let categoryName = null;
            let subCategoryName = null;
            if (selectedCategoryId && props?.categories) {
                const cat = props.categories.find(c => Number(c.id) === Number(selectedCategoryId));
                categoryName = cat?.name || null;
            }
            if (subCategoryId && availableSubCategories) {
                const sub = availableSubCategories[subCategoryId];
                subCategoryName = sub?.name || null;
            }

            // Collect current measurements for this subcategory
            const measurements = [];
            measurementsInstances.filter(mi => Number(mi.subcategory_id) === Number(subCategoryId)).forEach(inst => {
                const scid = inst.subcategory_id;
                const qty = measurementCounts[scid] || 1;
                const sizeVal = Array.isArray(sizeValues[scid]) ? (sizeValues[scid][0] || '') : (sizeValues[scid] || '');
                const widthVal = Array.isArray(widthValues[scid]) ? (widthValues[scid][0] || '') : (widthValues[scid] || '');
                if (measurementValues[inst.instanceKey] !== undefined && String(measurementValues[inst.instanceKey]).trim() !== '') {
                    measurements.push({
                        measurement_template_id: Number(inst.id),
                        value: measurementValues[inst.instanceKey],
                        category_id: inst.category_id || null,
                        sub_category_id: scid || null,
                        size: sizeVal,
                        width: widthVal,
                        quantity: qty,
                    });
                }
            });

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
                    category_id: selectedCategoryId,
                    category_name: categoryName,
                    sub_category_id: subCategoryId,
                    sub_category_name: subCategoryName,
                    measurements, // attach measurements to cart item
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
                category_id: item.category_id || null,
                category_name: item.category_name || null,
                subcategory_id: item.sub_category_id || null,
                sub_category_name: item.sub_category_name || null,
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

    if (!selectedCategoryId) {
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

    // Require subcategory
    if (!subCategoryId) {
        toast.error("Please select subcategory");
        return false;
    }

    // Require width/size per selected model (sub-category)
    if (!widthValues[subCategoryId] || String(widthValues[subCategoryId]).trim() === '') {
        toast.error('Please enter width for selected model');
        return false;
    }
    if (!sizeValues[subCategoryId] || String(sizeValues[subCategoryId]).trim() === '') {
        toast.error('Please select size for selected model');
        return false;
    }

    if (discount > subTotal) {
        toast.error("Discount cannot exceed subtotal");
        return false;
    }

    // 🔴 MEASUREMENT REQUIRED VALIDATION (per model instance)
    if (measurementsInstances.length > 0) {
        for (let m of measurementsInstances) {
            const key = m.instanceKey;
            if (!measurementValues[key] || measurementValues[key].trim() === "") {
                toast.error(`Please enter ${m.name} for ${m.subcategory_name}`);
                return false;
            }
        }
    }

    return true;
};


// 🔹 Build measurement payload from all cart items
const buildMeasurementPayload = () => {
    const payload = [];
    cartItems.forEach(item => {
        if (Array.isArray(item.measurements)) {
            item.measurements.forEach(m => {
                payload.push({ ...m });
            });
        }
    });
    return payload;
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
        // send imploded width/size in the order of selected subcategories (models)
        // Flatten all group widths/sizes for all subcategories (pipe for groups, comma for subcategories)
        width: Array.isArray(widthValues[subCategoryId]) ? widthValues[subCategoryId].join('|') : String(widthValues[subCategoryId] || ''),
        size: Array.isArray(sizeValues[subCategoryId]) ? sizeValues[subCategoryId].join('|') : String(sizeValues[subCategoryId] || ''),
       
        customer_mobile: "",
        other_discount: discount,
        round_off: grandTotal - (subTotal - discount),
        gross_amount: subTotal,
        item_discount: 0,
        tax_amount: 0,
        total: subTotal - discount,
        grand_total: grandTotal,
        service_charge: serviceCharge || 0,


        // single category_id
        category_id: selectedCategoryId,
        measurements: buildMeasurementPayload(),

        items: buildItemsPayload(),
        sub_category_id: subCategoryId,
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
        customPaymentData.balanceDue > 0 ? "draft" : "draft";
} else {
    payload.custom_payment_data = null;
    payload.status = "draft";
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



    // derive a primary category id for components that expect a single category
    const primaryCategoryId = selectedCategoryId || null;

    return (
        <div className="main-wrapper">
            <div className="page-wrapper pos-pg-wrapper ms-0">
                <TopMenu onHomeClick={() => window.location.href = '/'} />
                <div className="content pos-design p-0">
                    <div className="row pos-wrapper align-items-start">

                        {/* LEFT SIDEBAR: Categories */}
                        <div className="col-12 col-lg-2 pe-0">

                            {/* MainCategorySidebar in left sidebar */}
                            <MainCategorySidebar selectedId={selectedMainCategoryIds} onSelect={setSelectedMainCategoryIds} />



                               
    
                        </div>

                        

                        

                  
                            {/* SubCategory above Customer */}
                           
                            

                        {/* CENTER: Product Grid */}
                            <div className="col-md-12 col-lg-7">
                                {/* CustomerSelect above CategorySidebar */}
                                <div className="mb-2">
                                    <label className="fw-bold mb-1">Customer</label>
                                    <div className="d-flex gap-2 align-items-center">
                                        <div style={{ flex: 1 }}>
                                            <CustomerSelect
                                                value={customerId}
                                                onChange={setCustomerId}
                                                newCustomer={addedCustomer}
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
                                {/* CategorySidebar just above model selection */}
                                <div className="mb-1" style={{ marginBottom: '4px' }}>
                                    <CategorySidebar selectedId={selectedCategoryId} onSelect={setSelectedCategoryId} />
                                </div>

                                {primaryCategoryId && (
                                    <div className="mb-1" style={{ marginBottom: '4px' }}>
                                        <SubCategorySelect
                                            categoryId={selectedCategoryId}
                                            selectedSubId={subCategoryId}
                                            onSelect={setSubCategoryId}
                                        />
                                        {/* Per-model (sub-category) width/size inputs (single input, no add/remove) */}
                                        {/* Width/Size inputs are now inside the Measurements card below */}
                                    </div>
                                )}

                        
                                
                              

            {measurementsInstances.length > 0 && selectedCategoryId && subCategoryId && (
                <div className="card mt-2 p-2">
                    <h6 className="fw-bold mb-2">Measurements</h6>
                    {/* Only one subcategory (model) */}
                    {(() => {
                        const scid = subCategoryId;
                        const subMeasurements = measurementsInstances.filter(mi => Number(mi.subcategory_id) === Number(scid));
                        const sub = availableSubCategories[scid] || {};
                        const catName = props?.categories?.find(c => Number(c.id) === Number(sub.measurement_category_id))?.name || (sub.measurement_category_id ? `Category ${sub.measurement_category_id}` : 'Category');
                        const subName = sub.name || `Model ${scid}`;
                        return (
                            <div key={`grp-${scid}`} className="mb-3">
                                <div className="fw-bold mb-1">{subName}</div>
                                <div className="d-flex gap-2 mb-2">
                                    <div style={{ flex: 1 }}>
                                        <label className="form-label">Width ({catName} - {subName})</label>
                                        <input
                                            type="text"
                                            className="form-control form-control-sm"
                                            value={Array.isArray(widthValues[scid]) ? (widthValues[scid][0] || '') : (widthValues[scid] || '')}
                                            onChange={(e) => setWidthValues(prev => ({
                                                ...prev,
                                                [scid]: [e.target.value]
                                            }))}
                                        />
                                    </div>
                                    <div style={{ width: 160 }}>
                                        <label className="form-label">Size ({catName} - {subName})</label>
                                        <select
                                            className="form-select form-select-sm"
                                            value={Array.isArray(sizeValues[scid]) ? (sizeValues[scid][0] || '') : (sizeValues[scid] || '')}
                                            onChange={(e) => setSizeValues(prev => ({
                                                ...prev,
                                                [scid]: [e.target.value]
                                            }))}
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
                                    <div style={{ width: 120 }}>
                                        <label className="form-label">Quantity ({subName})</label>
                                        <input
                                            type="number"
                                            min={1}
                                            className="form-control form-control-sm"
                                            value={measurementCounts[scid] || 1}
                                            onChange={e => {
                                                const val = Math.max(1, Number(e.target.value) || 1);
                                                setMeasurementCounts(prev => ({ ...prev, [scid]: val }));
                                            }}
                                        />
                                    </div>
                                </div>
                                <div className="row mb-2 align-items-end">
                                    {subMeasurements.map(m => {
                                        // Parse values from template (assume m.values is comma-separated string or undefined)
                                        let options = [];
                                        if (m.values && typeof m.values === 'string' && m.values.trim() !== '') {
                                            options = m.values.split(',').map(v => v.trim()).filter(Boolean);
                                        }
                                        return (
                                            <div key={m.instanceKey} className="col-md-4 mb-2">
                                                <label className="form-label">{m.name}</label>
                                                {options.length > 0 ? (
                                                    <select
                                                        className="form-select form-select-sm"
                                                        value={measurementValues[m.instanceKey] || ""}
                                                        onChange={e => setMeasurementValues(prev => ({ ...prev, [m.instanceKey]: e.target.value }))}
                                                    >
                                                        <option value="">Select {m.name}</option>
                                                        {options.map(opt => (
                                                            <option key={opt} value={opt}>{opt}</option>
                                                        ))}
                                                    </select>
                                                ) : (
                                                    <input
                                                        type="text"
                                                        className="form-control form-control-sm"
                                                        placeholder={`Enter ${m.name}`}
                                                        value={measurementValues[m.instanceKey] || ""}
                                                        onChange={e => setMeasurementValues(prev => ({ ...prev, [m.instanceKey]: e.target.value }))}
                                                    />
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })()}
                </div>
            )}




                            <div className="mb-2">
                                <label className="fw-bold mb-1">Employee</label>
                                <EmployeeSelect value={employeeId} onChange={setEmployeeId} />
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
                                                <div className="text-muted small">
                                                    Model: {item.sub_category_name || (item.sub_category_id ? `ID: ${item.sub_category_id}` : '-')}
                                                </div>
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
                                                                    <div className="text-muted small mt-1">
                                                                        Model: {editingItem.sub_category_name || (editingItem.sub_category_id ? `ID: ${editingItem.sub_category_id}` : '-')}
                                                                    </div>
                                                                </div>
                                                                <div className="row g-2">
                                                                    <div className="col-6">
                                                                        <label className="form-label">Quantity</label>
                                                                        <input
                                                                            type="hidden"
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

                                Reference & Discount
                               

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
                    employee={selectedEmployee}
                />
            )}
            {showCustomerModal && (
                <CustomerDetailsModal
                    open={showCustomerModal}
                    onClose={() => setShowCustomerModal(false)}
                    customerId={customerId}
                    initialDetails={customerDetails}
                    onSaved={(updated) => {
                        // set newly created/updated customer as selected and refresh details
                        if (updated && updated.id) {
                            setCustomerId(updated.id);
                        }
                        setCustomerDetails((prev) => ({ ...prev, customer: updated }));
                        setShowCustomerModal(false);
                    }}
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

           <AdvancePaymentModal
    open={showAdvanceModal}
    onClose={() => setShowAdvanceModal(false)}
    grandTotal={grandTotal}
    onSave={(data) => setCustomPaymentData(data)}
/>


        </div>
    );
}

import React from 'react';

export default function TopMenu({ onHomeClick }) {
    return (
        <div className="top-menu d-flex align-items-center justify-content-between p-2 bg-white shadow-sm" style={{ borderBottom: '1px solid rgba(0,0,0,0.05)' }}>
            <div className="d-flex align-items-center gap-2">
                <button className="btn btn-sm btn-light" onClick={onHomeClick} title="Home">
                    <i className="fa fa-home"></i>
                </button>
                <h5 className="mb-0 ms-2">Booking</h5>
            </div>
            <div>
                {/* Placeholder for other top menu items if needed */}
            </div>
        </div>
    );
}

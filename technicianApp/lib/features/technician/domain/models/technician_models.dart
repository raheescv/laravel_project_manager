import 'package:invo/shared/domain/helpers/formatters.dart';

/// A single assigned-complaint row (technician list / dashboard recent).
/// Mirrors App\Http\Resources\V1\Technician\ComplaintListResource.
class ComplaintListItem {
  ComplaintListItem({
    required this.id,
    required this.registrationId,
    required this.status,
    required this.statusLabel,
    required this.statusColor,
    required this.complaintName,
    required this.categoryName,
    required this.technicianRemark,
    required this.propertyNumber,
    required this.building,
    required this.group,
    required this.priority,
    required this.priorityLabel,
    required this.priorityColor,
    required this.date,
    required this.time,
    required this.customerName,
    this.customerMobile = '',
  });

  final int id;
  final String registrationId;
  final String status;
  final String statusLabel;
  final String statusColor;
  final String complaintName;
  final String categoryName;
  final String technicianRemark;
  final String propertyNumber;
  final String building;
  final String group;
  final String priority;
  final String priorityLabel;
  final String priorityColor;
  final String date; // yyyy-MM-dd
  final String time;
  final String customerName;
  final String customerMobile;

  factory ComplaintListItem.fromJson(Map<String, dynamic> j) =>
      ComplaintListItem(
        id: asNum(j['id']).toInt(),
        registrationId: asStr(j['registration_id']),
        status: asStr(j['status']),
        statusLabel: asStr(j['status_label']),
        statusColor: asStr(j['status_color']),
        complaintName: asStr(j['complaint_name']),
        categoryName: asStr(j['category_name']),
        technicianRemark: asStr(j['technician_remark']),
        propertyNumber: asStr(j['property_number']),
        building: asStr(j['building']),
        group: asStr(j['group']),
        priority: asStr(j['priority']),
        priorityLabel: asStr(j['priority_label']),
        priorityColor: asStr(j['priority_color']),
        date: asStr(j['date']),
        time: asStr(j['time']),
        customerName: asStr(j['customer_name']),
        customerMobile: asStr(j['customer_mobile']),
      );
}

/// Dashboard workload summary. Mirrors TechnicianController::dashboard.
class TechnicianDashboard {
  TechnicianDashboard({
    required this.technicianName,
    required this.counts,
    required this.priority,
    required this.next,
    required this.week,
    required this.recent,
  });

  final String technicianName;
  final DashboardCounts counts;
  final PriorityBreakdown priority;

  /// The most urgent open job ("Up next" spotlight) — null when nothing is open.
  final ComplaintListItem? next;

  /// Completions per day, last 7 days, oldest first.
  final List<DayStat> week;
  final List<ComplaintListItem> recent;

  factory TechnicianDashboard.fromJson(Map<String, dynamic> j) {
    final tech = Map<String, dynamic>.from(j['technician'] ?? const {});
    return TechnicianDashboard(
      technicianName: asStr(tech['name']),
      counts: DashboardCounts.fromJson(
          Map<String, dynamic>.from(j['counts'] ?? const {})),
      priority: PriorityBreakdown.fromJson(
          Map<String, dynamic>.from(j['priority'] ?? const {})),
      next: j['next'] is Map
          ? ComplaintListItem.fromJson(Map<String, dynamic>.from(j['next']))
          : null,
      week: (j['week'] as List? ?? const [])
          .map((e) => DayStat.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      recent: (j['recent'] as List? ?? const [])
          .map((e) => ComplaintListItem.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

/// One bar of the dashboard "your week" chart.
class DayStat {
  DayStat({required this.date, required this.label, required this.count});

  final String date; // yyyy-MM-dd
  final String label; // Mon, Tue, …
  final int count;

  factory DayStat.fromJson(Map<String, dynamic> j) => DayStat(
        date: asStr(j['date']),
        label: asStr(j['label']),
        count: asNum(j['count']).toInt(),
      );
}

class DashboardCounts {
  DashboardCounts({
    required this.assigned,
    required this.pending,
    required this.outstanding,
    required this.completedToday,
    required this.completedWeek,
  });

  final int assigned;
  final int pending;
  final int outstanding;
  final int completedToday;
  final int completedWeek;

  factory DashboardCounts.fromJson(Map<String, dynamic> j) => DashboardCounts(
        assigned: asNum(j['assigned']).toInt(),
        pending: asNum(j['pending']).toInt(),
        outstanding: asNum(j['outstanding']).toInt(),
        completedToday: asNum(j['completed_today']).toInt(),
        completedWeek: asNum(j['completed_week']).toInt(),
      );
}

class PriorityBreakdown {
  PriorityBreakdown({
    required this.critical,
    required this.high,
    required this.medium,
    required this.low,
  });

  final int critical;
  final int high;
  final int medium;
  final int low;

  int get total => critical + high + medium + low;

  factory PriorityBreakdown.fromJson(Map<String, dynamic> j) =>
      PriorityBreakdown(
        critical: asNum(j['critical']).toInt(),
        high: asNum(j['high']).toInt(),
        medium: asNum(j['medium']).toInt(),
        low: asNum(j['low']).toInt(),
      );
}

/// Full complaint detail. Mirrors ComplaintDetailResource / loadData().
class ComplaintDetail {
  ComplaintDetail({
    required this.id,
    required this.status,
    required this.statusLabel,
    required this.statusColor,
    required this.isCompleted,
    required this.isCancelled,
    required this.technicianRemark,
    required this.propertyInfo,
    required this.customerInfo,
    required this.activityLog,
    required this.allComplaints,
    required this.supplyRequest,
  });

  final int id;
  final String status;
  final String statusLabel;
  final String statusColor;
  final bool isCompleted;
  final bool isCancelled;
  final String technicianRemark;
  final PropertyInfo propertyInfo;
  final CustomerInfo customerInfo;
  final ActivityLog activityLog;
  final List<SiblingComplaint> allComplaints;
  final SupplyRequest supplyRequest;

  /// The workflow is read-only once completed or cancelled.
  bool get isLocked => isCompleted || isCancelled;

  factory ComplaintDetail.fromJson(Map<String, dynamic> j) => ComplaintDetail(
        id: asNum(j['id']).toInt(),
        status: asStr(j['status']),
        statusLabel: asStr(j['status_label']),
        statusColor: asStr(j['status_color']),
        isCompleted: j['is_completed'] == true,
        isCancelled: j['is_cancelled'] == true,
        technicianRemark: asStr(j['technician_remark']),
        propertyInfo: PropertyInfo.fromJson(
            Map<String, dynamic>.from(j['property_info'] ?? const {})),
        customerInfo: CustomerInfo.fromJson(
            Map<String, dynamic>.from(j['customer_info'] ?? const {})),
        activityLog: ActivityLog.fromJson(
            Map<String, dynamic>.from(j['activity_log'] ?? const {})),
        allComplaints: (j['all_complaints'] as List? ?? const [])
            .map((e) => SiblingComplaint.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
        supplyRequest: SupplyRequest.fromJson(
            Map<String, dynamic>.from(j['supply_request'] ?? const {})),
      );
}

class PropertyInfo {
  PropertyInfo({
    required this.registrationId,
    required this.group,
    required this.building,
    required this.type,
    required this.propertyNumber,
    required this.priority,
    required this.priorityColor,
    required this.date,
    required this.time,
  });

  final String registrationId;
  final String group;
  final String building;
  final String type;
  final String propertyNumber;
  final String priority;
  final String priorityColor;
  final String date;
  final String time;

  factory PropertyInfo.fromJson(Map<String, dynamic> j) => PropertyInfo(
        registrationId: asStr(j['registration_id']),
        group: asStr(j['group']),
        building: asStr(j['building']),
        type: asStr(j['type']),
        propertyNumber: asStr(j['property_number']),
        priority: asStr(j['priority']),
        priorityColor: asStr(j['priority_color']),
        date: asStr(j['date']),
        time: asStr(j['time']),
      );
}

class CustomerInfo {
  CustomerInfo({
    required this.complaintStatus,
    required this.complaintStatusColor,
    required this.rentoutId,
    required this.rentoutStatus,
    required this.agreementStartDate,
    required this.customerName,
    required this.customerMobile,
    required this.workOrderNo,
  });

  final String complaintStatus;
  final String complaintStatusColor;
  final String rentoutId;
  final String rentoutStatus;
  final String agreementStartDate;
  final String customerName;
  final String customerMobile;
  final String workOrderNo;

  factory CustomerInfo.fromJson(Map<String, dynamic> j) => CustomerInfo(
        complaintStatus: asStr(j['complaint_status']),
        complaintStatusColor: asStr(j['complaint_status_color']),
        rentoutId: asStr(j['rentout_id']),
        rentoutStatus: asStr(j['rentout_status']),
        agreementStartDate: asStr(j['agreement_start_date']),
        customerName: asStr(j['customer_name']),
        customerMobile: asStr(j['customer_mobile']),
        workOrderNo: asStr(j['work_order_no']),
      );
}

class ActivityLog {
  ActivityLog({
    required this.createdBy,
    required this.createdAt,
    required this.assignedBy,
    required this.assignedAt,
    required this.completedBy,
    required this.completedAt,
  });

  final String createdBy;
  final String createdAt;
  final String assignedBy;
  final String assignedAt;
  final String completedBy;
  final String completedAt;

  factory ActivityLog.fromJson(Map<String, dynamic> j) => ActivityLog(
        createdBy: asStr(j['created_by']),
        createdAt: asStr(j['created_at']),
        assignedBy: asStr(j['assigned_by']),
        assignedAt: asStr(j['assigned_at']),
        completedBy: asStr(j['completed_by']),
        completedAt: asStr(j['completed_at']),
      );
}

class SiblingComplaint {
  SiblingComplaint({
    required this.id,
    required this.categoryName,
    required this.complaintName,
    required this.technicianName,
    required this.technicianRemark,
    required this.status,
    required this.statusLabel,
    required this.statusColor,
    required this.isCurrent,
  });

  final int id;
  final String categoryName;
  final String complaintName;
  final String technicianName;
  final String technicianRemark;
  final String status;
  final String statusLabel;
  final String statusColor;
  final bool isCurrent;

  factory SiblingComplaint.fromJson(Map<String, dynamic> j) => SiblingComplaint(
        id: asNum(j['id']).toInt(),
        categoryName: asStr(j['category_name']),
        complaintName: asStr(j['complaint_name']),
        technicianName: asStr(j['technician_name']),
        technicianRemark: asStr(j['technician_remark']),
        status: asStr(j['status']),
        statusLabel: asStr(j['status_label']),
        statusColor: asStr(j['status_color']),
        isCurrent: j['is_current'] == true,
      );
}

class SupplyRequest {
  SupplyRequest({
    required this.id,
    required this.total,
    required this.otherCharges,
    required this.grandTotal,
    required this.items,
    required this.notes,
    required this.images,
  });

  final int? id;
  final double total;
  final double otherCharges;
  final double grandTotal;
  final List<SupplyItem> items;
  final List<SupplyNote> notes;
  final List<Attachment> images;

  factory SupplyRequest.fromJson(Map<String, dynamic> j) => SupplyRequest(
        id: j['id'] == null ? null : asNum(j['id']).toInt(),
        total: asNum(j['total']).toDouble(),
        otherCharges: asNum(j['other_charges']).toDouble(),
        grandTotal: asNum(j['grand_total']).toDouble(),
        items: (j['items'] as List? ?? const [])
            .map((e) => SupplyItem.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
        notes: (j['notes'] as List? ?? const [])
            .map((e) => SupplyNote.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
        images: (j['images'] as List? ?? const [])
            .map((e) => Attachment.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
      );
}

class SupplyItem {
  SupplyItem({
    required this.id,
    required this.branchId,
    required this.branchName,
    required this.productId,
    required this.productName,
    required this.mode,
    required this.quantity,
    required this.unitPrice,
    required this.total,
    required this.remarks,
  });

  final int id;
  final int? branchId;
  final String branchName;
  final int? productId;
  final String productName;
  final String mode; // New | Damaged
  final double quantity;
  final double unitPrice;
  final double total;
  final String remarks;

  factory SupplyItem.fromJson(Map<String, dynamic> j) => SupplyItem(
        id: asNum(j['id']).toInt(),
        branchId: j['branch_id'] == null ? null : asNum(j['branch_id']).toInt(),
        branchName: asStr(j['branch_name']),
        productId:
            j['product_id'] == null ? null : asNum(j['product_id']).toInt(),
        productName: asStr(j['product_name']),
        mode: asStr(j['mode']).isEmpty ? 'New' : asStr(j['mode']),
        quantity: asNum(j['quantity']).toDouble(),
        unitPrice: asNum(j['unit_price']).toDouble(),
        total: asNum(j['total']).toDouble(),
        remarks: asStr(j['remarks']),
      );
}

class SupplyNote {
  SupplyNote({
    required this.id,
    required this.note,
    required this.creator,
    required this.createdAt,
  });

  final int id;
  final String note;
  final String creator;
  final String createdAt;

  factory SupplyNote.fromJson(Map<String, dynamic> j) => SupplyNote(
        id: asNum(j['id']).toInt(),
        note: asStr(j['note']),
        creator: asStr(j['creator']),
        createdAt: asStr(j['created_at']),
      );
}

class Attachment {
  Attachment({
    required this.id,
    required this.name,
    required this.type,
    required this.path,
    required this.isImage,
    required this.isVideo,
    required this.isPdf,
  });

  final int id;
  final String name;
  final String type;
  final String path; // absolute URL
  final bool isImage;
  final bool isVideo;
  final bool isPdf;

  factory Attachment.fromJson(Map<String, dynamic> j) => Attachment(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        type: asStr(j['type']),
        path: asStr(j['path']),
        isImage: j['is_image'] == true,
        isVideo: j['is_video'] == true,
        isPdf: j['is_pdf'] == true,
      );
}

/// A product option for the supply-item picker (`/api/v1/products`).
class ProductOption {
  ProductOption({
    required this.id,
    required this.name,
    required this.barcode,
    required this.cost,
  });

  final int id;
  final String name;
  final String barcode;
  final double cost;

  factory ProductOption.fromJson(Map<String, dynamic> j) => ProductOption(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
        barcode: asStr(j['barcode']),
        cost: asNum(j['cost']).toDouble(),
      );
}

/// A store/branch option for the supply-item store picker (`/api/v1/branches`).
class BranchOption {
  BranchOption({required this.id, required this.name});

  final int id;
  final String name;

  factory BranchOption.fromJson(Map<String, dynamic> j) => BranchOption(
        id: asNum(j['id']).toInt(),
        name: asStr(j['name']),
      );
}

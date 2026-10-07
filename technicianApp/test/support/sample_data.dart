// Realistic sample payloads, shaped exactly like the API's JSON and parsed
// through the app's own `fromJson` factories — so the tests exercise parsing
// as well as rendering.
import 'package:intl/intl.dart';

import 'package:invo/features/checklist/domain/models/checklist_models.dart';
import 'package:invo/features/technician/domain/models/technician_models.dart';
import 'package:invo/shared/domain/models/index.dart';

String _iso(DateTime d) => DateFormat('yyyy-MM-dd').format(d);
DateTime get _today {
  final n = DateTime.now();
  return DateTime(n.year, n.month, n.day);
}

String dayOffset(int days) => _iso(_today.add(Duration(days: days)));

// ---------------------------------------------------------------- user ----

Map<String, dynamic> userJson({String name = 'Rahul Nair', String photo = '', String mobile = '55512345'}) => {
      'id': 7,
      'name': name,
      'code': '2022',
      'email': 'rahul@employee.local',
      'mobile': mobile,
      'is_admin': false,
      'designation': 'Facility Coordinator',
      'branch_id': 1,
      'role': 'Technician',
      'photo': photo,
      'sale_day_session_status': 'open',
      'sale_day_session_date': dayOffset(0),
      'permissions': <String>[],
    };

ApiUser sampleUser({String name = 'Rahul Nair', String photo = '', String mobile = '55512345'}) =>
    ApiUser.fromJson(userJson(name: name, photo: photo, mobile: mobile));

// ---------------------------------------------------------- complaints ----

Map<String, dynamic> complaintRowJson(int id,
        {String status = 'assigned', String priority = 'high', String name = 'AC not cooling', int day = 0}) =>
    {
      'id': id,
      'registration_id': 'MR-${1000 + id}',
      'status': status,
      'status_label': status == 'completed' ? 'Completed' : 'Assigned',
      'status_color': status == 'completed' ? 'success' : 'info',
      'complaint_name': name,
      'category_name': 'HVAC',
      'technician_remark': '',
      'property_number': '${300 + id}',
      'building': 'Porto Arabia Tower 11',
      'group': 'The Pearl',
      'priority': priority,
      'priority_label': priority[0].toUpperCase() + priority.substring(1),
      'priority_color': priority == 'critical' ? 'danger' : (priority == 'high' ? 'warning' : 'info'),
      'date': dayOffset(day),
      'time': '10:30 AM',
      'customer_name': 'Leena Varghese',
      'customer_mobile': '55512345',
    };

List<ComplaintListItem> sampleComplaints([int count = 6]) => [
      for (var i = 1; i <= count; i++)
        ComplaintListItem.fromJson(complaintRowJson(i,
            status: i == count ? 'completed' : 'assigned',
            priority: const ['critical', 'high', 'medium', 'low'][i % 4],
            name: const ['AC not cooling', 'Leaking kitchen tap', 'Door lock jammed', 'Light flickering'][i % 4],
            day: -i)),
    ];

TechnicianDashboard sampleDashboard() => TechnicianDashboard.fromJson({
      'technician': {'name': 'Rahul Nair'},
      'counts': {'assigned': 4, 'pending': 2, 'outstanding': 1, 'completed_today': 3, 'completed_week': 11},
      'priority': {'critical': 1, 'high': 2, 'medium': 3, 'low': 1},
      'next': complaintRowJson(1, priority: 'critical'),
      'week': [
        for (var i = 6; i >= 0; i--)
          {'date': dayOffset(-i), 'label': DateFormat('E').format(_today.subtract(Duration(days: i))), 'count': (i * 3) % 5},
      ],
      'recent': [for (var i = 1; i <= 4; i++) complaintRowJson(i)],
    });

Map<String, dynamic> complaintDetailJson(int id, {bool completed = false}) => {
      'id': id,
      'status': completed ? 'completed' : 'assigned',
      'status_label': completed ? 'Completed' : 'Assigned',
      'status_color': completed ? 'success' : 'info',
      'is_completed': completed,
      'is_cancelled': false,
      'technician_remark': completed ? 'Replaced the capacitor.' : '',
      'property_info': {
        'registration_id': 'MR-${1000 + id}',
        'group': 'The Pearl',
        'building': 'Porto Arabia Tower 11',
        'type': 'Apartment',
        'property_number': '${300 + id}',
        'priority': 'high',
        'priority_color': 'warning',
        'segment': 'Residential',
        'segment_color': 'info',
        'date': dayOffset(-1),
        'time': '10:30 AM',
      },
      'customer_info': {
        'complaint_status': 'Assigned',
        'complaint_status_color': 'info',
        'rentout_id': '88',
        'rentout_status': 'Occupied',
        'agreement_start_date': dayOffset(-200),
        'customer_name': 'Leena Varghese',
        'customer_mobile': '55512345',
        'work_order_no': 'WO-2231',
      },
      'activity_log': {
        'created_by': 'Front Desk',
        'created_at': '${dayOffset(-2)} 09:10',
        'assigned_by': 'Sara Mathew',
        'assigned_at': '${dayOffset(-2)} 09:30',
        'completed_by': completed ? 'Rahul Nair' : '',
        'completed_at': completed ? '${dayOffset(0)} 12:00' : '',
      },
      'all_complaints': [
        {
          'id': id,
          'category_name': 'HVAC',
          'complaint_name': 'AC not cooling',
          'technician_name': 'Rahul Nair',
          'technician_remark': '',
          'status': 'assigned',
          'status_label': 'Assigned',
          'status_color': 'info',
          'is_current': true,
        },
        {
          'id': id + 100,
          'category_name': 'Plumbing',
          'complaint_name': 'Leaking kitchen tap',
          'technician_name': 'Omar Haddad',
          'technician_remark': 'Washer replaced',
          'status': 'completed',
          'status_label': 'Completed',
          'status_color': 'success',
          'is_current': false,
        },
      ],
      'supply_request': {
        'id': 5,
        'total': 240,
        'other_charges': 20,
        'grand_total': 260,
        'items': [
          {
            'id': 1,
            'branch_id': 1,
            'branch_name': 'Main Store',
            'product_id': 9,
            'product_name': 'AC capacitor 35uF',
            'mode': 'New',
            'quantity': 2,
            'unit_price': 120,
            'total': 240,
            'remarks': 'Urgent',
          },
        ],
        'notes': [
          {'id': 1, 'note': 'Customer available after 4pm', 'creator': 'Sara Mathew', 'created_at': '${dayOffset(-1)} 15:00'},
        ],
        'images': [
          {'id': 1, 'name': 'before.jpg', 'type': 'image/jpeg', 'path': '/storage/complaints/1.jpg', 'is_image': true},
          {'id': 2, 'name': 'invoice.pdf', 'type': 'application/pdf', 'path': '/storage/complaints/2.pdf', 'is_pdf': true},
        ],
      },
    };

ComplaintDetail sampleComplaintDetail(int id, {bool completed = false}) =>
    ComplaintDetail.fromJson(complaintDetailJson(id, completed: completed));

// ----------------------------------------------------------- checklists ----

Map<String, dynamic> _line(int id, String name, String category,
        {String? inStatus,
        String? outStatus,
        String? inImage,
        String? outImage,
        String? ref,
        String? outComment,
        double cost = 0,
        num qty = 1}) =>
    {
      'id': id,
      'checklist_id': 33,
      'name': name,
      'category': category,
      'qty': qty,
      'sort_order': id,
      'reference_image': ref,
      'move_in_image': inImage,
      'move_out_image': outImage,
      'move_in_status': inStatus,
      'move_in_comment': null,
      'move_out_status': outStatus,
      'move_out_comment': outComment,
      'damage_cost': cost,
    };

List<Map<String, dynamic>> _lines({required bool moveOut}) => [
      _line(501, 'Split AC unit', 'Living Room',
          inStatus: 'ok', outStatus: moveOut ? 'ok' : null, inImage: '/storage/c/ac-in.jpg', outImage: moveOut ? '/storage/c/ac-out.jpg' : null),
      _line(502, 'Ceiling lights', 'Living Room', inStatus: 'ok', qty: 4, ref: '/storage/c/lights.jpg'),
      _line(503, 'Sofa 3-seater', 'Living Room',
          inStatus: 'ok', outStatus: moveOut ? 'not_ok' : null, outComment: moveOut ? 'Stained, torn arm' : null, cost: moveOut ? 180 : 0),
      _line(504, 'Refrigerator', 'Kitchen', inStatus: 'ok', outStatus: moveOut ? 'ok' : null),
      _line(505, 'Cooker hood', 'Kitchen',
          inStatus: 'ok', outStatus: moveOut ? 'not_ok' : null, outComment: moveOut ? 'Filter clogged' : null, cost: moveOut ? 95.5 : 0),
      _line(506, 'Gas hob 4-burner', 'Kitchen', inStatus: null),
      _line(507, 'Wardrobe', 'Master Bedroom', inStatus: 'ok'),
      _line(508, 'Bed frame (king)', 'Master Bedroom', inStatus: 'ok', outStatus: moveOut ? 'ok' : null),
      _line(509, 'Apartment keys', '', inStatus: 'ok', qty: 3),
    ];

Map<String, dynamic> _sig(String role, String label, String assignee,
        {bool canSign = false, bool isMe = false, bool signed = false}) =>
    {
      'role': role,
      'label': label,
      'assignee_name': assignee,
      'can_sign': canSign,
      'is_me': isMe,
      'signed': signed,
      'signer_name': signed ? assignee : null,
      'signed_at': signed ? '${dayOffset(0)}T16:41:00+03:00' : null,
      'signature': signed ? '/storage/sig/$role.png' : null,
    };

List<Map<String, dynamic>> _signatures(int signed, {bool lease = false}) => [
      _sig('facility_coordinator', lease ? 'Site Engineer' : 'Facility Coordinator', 'Rahul Nair',
          canSign: true, isMe: true, signed: signed >= 1),
      _sig('lessee', 'Lessee', 'Leena Varghese', canSign: true, signed: signed >= 2),
      _sig('leasing_coordinator', lease ? 'Admin Coordinator' : 'Leasing Coordinator', 'Sara Mathew', signed: signed >= 3),
    ];

List<Map<String, dynamic>> _fixtures() => [
      {
        'id': 8,
        'category': 'Living Room',
        'owner_name': null,
        'owner_signed_at': null,
        'owner_signature': null,
        'ready_for_acceptance': true,
        'entries': [
          {
            'id': 90,
            'comments': 'Repaint feature wall',
            'status': 'completed',
            'status_label': 'Completed',
            'completed_date': dayOffset(-1),
            'before_image': '/storage/f/90-before.jpg',
            'after_image': '/storage/f/90-after.jpg',
          },
        ],
      },
      {
        'id': 7,
        'category': 'Kitchen',
        'owner_name': null,
        'owner_signed_at': null,
        'owner_signature': null,
        'ready_for_acceptance': false,
        'entries': [
          {
            'id': 91,
            'comments': 'Re-grout tiles behind the sink',
            'status': 'in_progress',
            'status_label': 'In Progress',
            'completed_date': null,
            'before_image': '/storage/f/91-before.jpg',
            'after_image': null,
          },
          {'id': 92, 'comments': '', 'status': 'pending', 'status_label': 'Pending', 'completed_date': null},
        ],
      },
      {
        'id': 9,
        'category': 'Master Bedroom',
        'owner_name': 'Mr. Khalid',
        'owner_signed_at': '${dayOffset(-1)}T11:20:00+03:00',
        'owner_signature': '/storage/f/owner-9.png',
        'ready_for_acceptance': true,
        'entries': [
          {'id': 93, 'comments': 'Fix wardrobe hinge', 'status': 'completed', 'status_label': 'Completed', 'completed_date': dayOffset(-2)},
        ],
      },
      {'id': null, 'category': 'Others', 'ready_for_acceptance': false, 'entries': <Map<String, dynamic>>[]},
    ];

Map<String, dynamic> _jobJson(int id,
        {required String phase,
        String phaseLabel = '',
        String agreementType = 'rental',
        String agreementLabel = 'Rental',
        List<String> phases = const ['move_in', 'move_out'],
        String? scheduled,
        int total = 9,
        int checked = 5,
        int damaged = 2,
        int signed = 1,
        bool sealed = false,
        String unit = '305'}) =>
    {
      'id': id,
      'unit': unit,
      'building': 'Porto Arabia Tower 11',
      'group': 'The Pearl',
      'agreement_type': agreementType,
      'agreement_label': agreementLabel,
      'reference_no': 'BAS/L/26 - 0$id',
      'lessee_name': 'Leena Varghese',
      'lessee_mobile': '55512345',
      'phase': phase,
      'phase_label': phaseLabel.isNotEmpty ? phaseLabel : (phase == 'move_out' ? 'Move-Out' : 'Move-In'),
      'phases': phases,
      'scheduled_date': scheduled,
      'my_roles': [
        {'role': 'facility_coordinator', 'label': agreementType == 'lease' ? 'Site Engineer' : 'Facility Coordinator'},
      ],
      'lines_total': total,
      'lines_checked': checked,
      'lines_damaged': damaged,
      'signatures_done': signed,
      'signatures_required': 3,
      'ready_to_seal': signed >= 3,
      'sealed': sealed,
    };

/// A rental move-out: four rooms, mixed statuses, photos, two damaged lines
/// with costs, fixtures in every state, and [signed] of three signatures.
ChecklistDetail rentalMoveOut({int signed = 1, bool sealed = false, int id = 12}) => ChecklistDetail.fromJson({
      ..._jobJson(id, phase: 'move_out', scheduled: dayOffset(0), signed: signed, sealed: sealed),
      'actual_date': null,
      'remarks': sealed ? 'Keys handed over' : null,
      'damage_total': 275.5,
      'lines': _lines(moveOut: true),
      'fixtures': _fixtures(),
      'signatures': _signatures(signed),
    });

/// The same unit at move-in (binary Present / not present).
ChecklistDetail rentalMoveIn({int id = 13}) => ChecklistDetail.fromJson({
      ..._jobJson(id, phase: 'move_in', scheduled: dayOffset(2), damaged: 0),
      'damage_total': 0,
      'lines': _lines(moveOut: false),
      'fixtures': _fixtures(),
      'signatures': _signatures(0),
    });

/// A lease / sale: one "Handover" phase (move_in), its own role labels.
ChecklistDetail leaseHandover({int id = 20}) => ChecklistDetail.fromJson({
      ..._jobJson(id,
          phase: 'move_in',
          phaseLabel: 'Handover',
          agreementType: 'lease',
          agreementLabel: 'Sale',
          phases: const ['move_in'],
          scheduled: dayOffset(-3),
          unit: 'Villa 7',
          damaged: 0),
      'damage_total': 0,
      'lines': _lines(moveOut: false),
      'fixtures': <Map<String, dynamic>>[],
      'signatures': _signatures(2, lease: true),
    });

/// Inbox rows across every date group, phase and state.
List<ChecklistJob> sampleJobs() => [
      ChecklistJob.fromJson(_jobJson(12, phase: 'move_out', scheduled: dayOffset(0))),
      ChecklistJob.fromJson(_jobJson(13, phase: 'move_in', scheduled: dayOffset(2), damaged: 0)),
      ChecklistJob.fromJson(_jobJson(14, phase: 'move_out', scheduled: dayOffset(-4), unit: '1802', signed: 3, checked: 9)),
      ChecklistJob.fromJson(_jobJson(20,
          phase: 'move_in',
          phaseLabel: 'Handover',
          agreementType: 'lease',
          agreementLabel: 'Sale',
          phases: const ['move_in'],
          scheduled: dayOffset(20),
          unit: 'Villa 7')),
      ChecklistJob.fromJson(_jobJson(15, phase: 'move_in', scheduled: null, unit: '1204')),
      ChecklistJob.fromJson(_jobJson(16, phase: 'move_out', scheduled: dayOffset(-30), sealed: true, signed: 3, checked: 9)),
    ];

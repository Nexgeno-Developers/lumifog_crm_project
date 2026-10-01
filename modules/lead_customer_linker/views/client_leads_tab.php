<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (isset($client)) {
    $client_id     = (int) $client->userid;
    $origin_leadid = !empty($client->leadid) ? (int) $client->leadid : 0;

    $this->db->select('tblleads.*, tblleads_status.name as status_name, tblleads_status.color as status_color, tblleads_sources.name as source_name, CONCAT(tblstaff.firstname, " ", tblstaff.lastname) as assigned_name');
    $this->db->from(db_prefix() . 'leads');
    $this->db->join(db_prefix() . 'leads_status', db_prefix() . 'leads_status.id = ' . db_prefix() . 'leads.status', 'left');
    $this->db->join(db_prefix() . 'leads_sources', db_prefix() . 'leads_sources.id = ' . db_prefix() . 'leads.source', 'left');
    $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'leads.assigned', 'left');
    
    // Match leads linked to this client OR the original lead that created this client
    $this->db->group_start();
    $this->db->where(db_prefix() . 'leads.client_id', $client_id);
    if ($origin_leadid > 0) {
        $this->db->or_where(db_prefix() . 'leads.id', $origin_leadid);
    }
    $this->db->group_end();

    $this->db->order_by(db_prefix() . 'leads.dateadded', 'DESC');
    $leads = $this->db->get()->result_array();
?>

<div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-4 tw-mb-4">
    <div>
        <h4 class="tw-my-0 tw-font-bold tw-text-lg tw-text-neutral-800 tw-flex tw-items-center tw-gap-2">
            <i class="fa-solid fa-crosshairs text-info"></i>
            <span>Associated Leads & Inquiries</span>
            <span class="badge badge-default tw-text-xs"><?= count($leads); ?></span>
        </h4>
        <p class="text-muted tw-text-sm tw-mt-1 tw-mb-0">
            Project opportunities and inquiries associated with <strong><?= html_escape($client->company); ?></strong>.
        </p>
    </div>
    <div class="tw-shrink-0">
        <?php if (staff_can('create', 'leads')) { ?>
            <a href="#" onclick="init_lead(); return false;" class="btn btn-primary btn-sm sm:btn-default">
                <i class="fa-regular fa-plus tw-mr-1"></i> New Lead
            </a>
        <?php } ?>
    </div>
</div>

<div class="clearfix"></div>
<hr class="hr-panel-heading tw-my-3" />

<?php if (!empty($leads)) { ?>
    <div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table class="table dt-table table-leads-customer scroll-responsive" data-order-col="5" data-order-type="desc" style="width: 100%;">
            <thead>
                <tr>
                    <th style="min-width: 50px;">#</th>
                    <th style="min-width: 180px;">Lead / Project</th>
                    <th style="min-width: 150px;">Contact</th>
                    <th style="min-width: 120px;">Assigned</th>
                    <th style="min-width: 90px;">Value</th>
                    <th style="min-width: 100px;">Status</th>
                    <th style="min-width: 130px;">Timeline</th>
                    <th style="min-width: 60px;" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $l) {
                    $status_color = !empty($l['status_color']) ? $l['status_color'] : '#777';
                    $is_origin = ($origin_leadid > 0 && (int) $l['id'] === $origin_leadid);
                ?>
                    <tr>
                        <td>
                            <a href="#" class="tw-font-semibold" onclick="init_lead(<?= (int) $l['id']; ?>); return false;">
                                #<?= (int) $l['id']; ?>
                            </a>
                        </td>
                        <td>
                            <div class="tw-font-medium">
                                <a href="#" onclick="init_lead(<?= (int) $l['id']; ?>); return false;">
                                    <?= html_escape($l['name']); ?>
                                </a>
                                <?php if ($is_origin) { ?>
                                    <span class="label label-info tw-ml-1" style="font-size: 10px; padding: 2px 6px;" title="Original lead used to create this customer record">
                                        <i class="fa fa-star tw-mr-1"></i> Origin Lead
                                    </span>
                                <?php } ?>
                            </div>
                            <?php if (!empty($l['title'])) { ?>
                                <div class="text-muted tw-text-xs tw-mt-0.5"><?= html_escape($l['title']); ?></div>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if (!empty($l['phonenumber'])) { ?>
                                <div><i class="fa fa-phone text-muted tw-mr-1 tw-text-xs"></i><a href="tel:<?= html_escape($l['phonenumber']); ?>"><?= html_escape($l['phonenumber']); ?></a></div>
                            <?php } ?>
                            <?php if (!empty($l['email'])) { ?>
                                <div class="tw-text-xs tw-mt-0.5"><i class="fa fa-envelope text-muted tw-mr-1 tw-text-xs"></i><a href="mailto:<?= html_escape($l['email']); ?>" class="text-muted"><?= html_escape($l['email']); ?></a></div>
                            <?php } ?>
                            <?php if (empty($l['phonenumber']) && empty($l['email'])) { ?>
                                <span class="text-muted">-</span>
                            <?php } ?>
                        </td>
                        <td>
                            <?= !empty($l['assigned_name']) ? html_escape($l['assigned_name']) : '<span class="text-muted">Unassigned</span>'; ?>
                        </td>
                        <td>
                            <?= !empty($l['lead_value']) && (float) $l['lead_value'] > 0 ? app_format_money($l['lead_value'], get_base_currency()) : '<span class="text-muted">-</span>'; ?>
                        </td>
                        <td>
                            <span class="label" style="color: #fff; background-color: <?= html_escape($status_color); ?>; font-size: 11px;">
                                <?= html_escape($l['status_name'] ?: 'Unknown'); ?>
                            </span>
                        </td>
                        <td data-order="<?= strtotime($l['dateadded']); ?>">
                            <div class="tw-text-xs">
                                <div><span class="text-muted">Added:</span> <?= _d(date('Y-m-d', strtotime($l['dateadded']))); ?></div>
                                <?php if (!empty($l['date_converted'])) { ?>
                                    <div class="text-success tw-mt-0.5"><i class="fa fa-check tw-mr-1"></i><?= _d(date('Y-m-d', strtotime($l['date_converted']))); ?></div>
                                <?php } ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="#" class="btn btn-default btn-icon btn-sm" onclick="init_lead(<?= (int) $l['id']; ?>); return false;" title="View Lead Details">
                                <i class="fa fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } else { ?>
    <div class="text-center text-muted mtop30 mbot30">
        <i class="fa-solid fa-crosshairs fa-3x text-muted" style="opacity: 0.3;"></i>
        <h4 class="mtop15">No leads linked to this customer yet</h4>
        <p>When you convert repeat project inquiries from this client, they will appear here with full timeline history.</p>
        <?php if (staff_can('create', 'leads')) { ?>
            <a href="#" onclick="init_lead(); return false;" class="btn btn-default mtop10">
                <i class="fa-regular fa-plus tw-mr-1"></i> Create Lead
            </a>
        <?php } ?>
    </div>
<?php } ?>

<?php } ?>

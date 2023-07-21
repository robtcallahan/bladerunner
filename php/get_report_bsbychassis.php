<?php

$sql = "
select c.deviceName,
case when b.businessService = 'Operations Hypervisors'
         then vm.businessService
         else b.businessService
end as businessService
from chassis c, blade b, vm
where b.chassisId = c.id
and vm.bladeId = b.id
-- and c.deviceName = 'chhpbc1'
group by c.deviceName, b.businessService, vm.businessService
order by c.deviceName, businessService";
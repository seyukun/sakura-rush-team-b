#!/bin/bash

export CTR_NAME=${1:-default}
export NS=ns-${CTR_NAME}
export HOST_VETH=veth-${CTR_NAME}
export CONT_IPADDR=10.200.1.2

create_container() {
        set -xe

        # コンテナルート
        mkdir -p ${HOME}/${CTR_NAME}
        cd ${HOME}/${CTR_NAME}
        if [ ! -d ${HOME}/${CTR_NAME}/rootfs ]; then
                sudo cp -r ${HOME}/debootstrap/rootfs ${HOME}/${CTR_NAME}/
        fi;
        echo debian | sudo chroot ${HOME}/${CTR_NAME}/rootfs tee /etc/hostname

        # コンテナ cgroup
        sudo mkdir -p /sys/fs/cgroup/${CTR_NAME}
        echo "200M" | sudo tee /sys/fs/cgroup/${CTR_NAME}/memory.max
        echo "64" | sudo tee /sys/fs/cgroup/${CTR_NAME}/pids.max
        echo "50000 100000" | sudo tee /sys/fs/cgroup/${CTR_NAME}/cpu.max

        # コンテナnet
        sudo ip netns add ${NS}

        sudo ip link add ${HOST_VETH} type veth peer name peereth0
        sudo ip link set peereth0 netns ${NS}

        sudo ip link set ${HOST_VETH} master ctrbr0
        sudo ip link set ${HOST_VETH} up

        sudo ip netns exec ${NS} ip link set peereth0 name eth0
        sudo ip netns exec ${NS} ip addr add ${CONT_IPADDR}/24 dev eth0
        sudo ip netns exec ${NS} ip link set lo up
        sudo ip netns exec ${NS} ip link set eth0 up
        sudo ip netns exec ${NS} ip route add default via 10.200.1.1

        # コンテナもろもろ
        sudo bash -c '
                set -xeu
                echo $$ > "'/sys/fs/cgroup/${CTR_NAME}/cgroup.procs'"
                ip netns exec "'${NS}'" unshare --fork --pid --mount --uts --ipc --mount-proc "'${HOME}/dockerless_launch.sh'" "'${HOME}/${CTR_NAME}'"
        '
}

cleanup() {
        set +e

        sudo rm -rf ${HOME}/${CTR_NAME}
        sudo cat /sys/fs/cgroup/${CTR_NAME}/cgroup.procs
        sudo awk '{ system("kill " $1) }' /sys/fs/cgroup/${CTR_NAME}/cgroup.procs
        sudo rmdir /sys/fs/cgroup/${CTR_NAME}
        sudo ip netns del "${NS}"
}

${HOME}/dockerless_init.sh
create_container || cleanup
